<?php

/**
 * IP-based geolocation for WP Pop! targeting.
 *
 * Provides a cached wrapper around three providers:
 *  1. ip-api.com        – free, no key, HTTP only, non-commercial terms.
 *  2. ipgeolocation.io  – API key required; 1 K req/day free tier.
 *  3. MaxMind GeoLite2  – local .mmdb database; free account required to download.
 *
 * Results are cached as WordPress transients keyed by the MD5 of the
 * visitor IP for the configured number of hours (default 24).
 *
 * @since   0.3.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Geo {

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Returns geo data for the current visitor.
	 *
	 * @return array|null  Keys: country_code (ISO 3166-1 alpha-2), region, city.
	 *                     Returns null when the lookup failed.
	 */
	public static function get_visitor_geo() {
		$ip = self::get_visitor_ip();
		return self::lookup( $ip );
	}

	/**
	 * Detects the real visitor IP, honouring common proxy / CDN headers.
	 *
	 * @return string
	 */
	public static function get_visitor_ip() {
		// Headers checked in descending order of trust.
		$headers = array(
			'HTTP_CF_CONNECTING_IP',  // Cloudflare real IP.
			'HTTP_X_REAL_IP',         // nginx upstream.
			'HTTP_X_FORWARDED_FOR',   // General proxy chain (first value = client).
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				// X-Forwarded-For may be a comma-separated list; take the first entry.
				$ip = trim( explode( ',', wp_unslash( $_SERVER[ $header ] ) )[0] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fall back to the direct connection address (may be private/loopback).
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Looks up geo data for the given IP, using the transient cache first.
	 *
	 * @param  string $ip
	 * @return array|null
	 */
	public static function lookup( $ip ) {
		if ( empty( $ip ) ) {
			return null;
		}

		// 1. Transient cache — keyed by IP hash to avoid storing raw IPs.
		$cache_key = 'wp_pop_geo_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			// '' means "tried before and failed".
			return '' !== $cached ? $cached : null;
		}

		$result = null;

		// 2. Run the configured provider.
		$provider = get_option( 'wp_pop_geo_provider', 'ip_api' );

		if ( 'ip_api' === $provider ) {
			$result = self::lookup_ip_api( $ip );
		} elseif ( 'ipgeolocation' === $provider ) {
			$api_key = get_option( 'wp_pop_geo_api_key', '' );
			if ( $api_key ) {
				$result = self::lookup_ipgeolocation( $ip, $api_key );
			}
		} elseif ( 'maxmind' === $provider ) {
			$result = self::lookup_maxmind( $ip );
		}

		// 3. If the configured provider failed and we have a Cloudflare country
		//    header, use it as a country-only fallback (no extra API call needed).
		if ( null === $result ) {
			$cf = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				: '';
			if ( $cf && 'XX' !== $cf ) {
				$result = array(
					'country_code' => $cf,
					'region'       => '',
					'city'         => '',
				);
			}
		}

		// 4. Cache the result (or '' to mark a failed lookup so we don't retry
		//    on every page view until the TTL expires).
		$ttl = absint( get_option( 'wp_pop_geo_cache_hours', 24 ) ) * HOUR_IN_SECONDS;
		set_transient( $cache_key, $result ?? '', $ttl );

		return $result;
	}

	/**
	 * Purges all cached geo transients (e.g. after changing the provider).
	 *
	 * @return void
	 */
	public static function purge_cache() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_wp_pop_geo_%'
			    OR option_name LIKE '_transient_timeout_wp_pop_geo_%'"
		);
	}

	// -------------------------------------------------------------------------
	// Providers
	// -------------------------------------------------------------------------

	/**
	 * ip-api.com — free for non-commercial use; no API key required.
	 * HTTP only on the free endpoint. Returns country, region, and city.
	 *
	 * @param  string $ip
	 * @return array|null
	 */
	private static function lookup_ip_api( $ip ) {
		// NOTE: ip-api.com is HTTP-only on the free tier and non-commercial only.
		// Commercial sites should use ipgeolocation.io or MaxMind instead.
		$url = 'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,message,countryCode,regionName,city';

		$response = wp_remote_get( $url, array( 'timeout' => 3 ) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 429 === $code ) {
			return null; // Rate limited.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) || 'success' !== ( $body['status'] ?? '' ) ) {
			return null;
		}

		return array(
			'country_code' => strtoupper( $body['countryCode'] ?? '' ),
			'region'       => $body['regionName'] ?? '',
			'city'         => $body['city'] ?? '',
		);
	}

	/**
	 * ipgeolocation.io — API key required; 1,000 req/day free tier.
	 * Full HTTPS; returns country, region (state_prov), and city.
	 *
	 * Sign up / upgrade: https://ipgeolocation.io/
	 *
	 * @param  string $ip
	 * @param  string $api_key
	 * @return array|null
	 */
	private static function lookup_ipgeolocation( $ip, $api_key ) {
		$url = add_query_arg(
			array(
				'apiKey' => $api_key,
				'ip'     => $ip,
				'fields' => 'country_code2,state_prov,city',
			),
			'https://api.ipgeolocation.io/ipgeo'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 3 ) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) || empty( $body['country_code2'] ) ) {
			return null;
		}

		return array(
			'country_code' => strtoupper( $body['country_code2'] ?? '' ),
			'region'       => $body['state_prov'] ?? '',
			'city'         => $body['city'] ?? '',
		);
	}

	/**
	 * MaxMind GeoLite2 local database.
	 * Requires the MaxMind\Db\Reader class and a downloaded .mmdb file.
	 * The reader is loaded from WooCommerce's vendor directory if available.
	 *
	 * @param  string $ip
	 * @return array|null
	 */
	private static function lookup_maxmind( $ip ) {
		$db_path = get_option( 'wp_pop_maxmind_db_path', '' );
		if ( ! $db_path || ! file_exists( $db_path ) ) {
			return null;
		}

		// Try to load the MaxMind DB reader from WooCommerce's vendor bundle.
		if ( ! class_exists( 'MaxMind\Db\Reader' ) ) {
			$wc_reader = WP_PLUGIN_DIR . '/woocommerce/vendor/maxmind-db/reader/src/MaxMind/Db/Reader.php';
			if ( file_exists( $wc_reader ) ) {
				require_once $wc_reader;
			}
		}

		if ( ! class_exists( 'MaxMind\Db\Reader' ) ) {
			return null; // Reader not available.
		}

		try {
			$reader = new MaxMind\Db\Reader( $db_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$record = $reader->get( $ip );
			$reader->close();

			if ( empty( $record ) ) {
				return null;
			}

			return array(
				'country_code' => strtoupper( $record['country']['iso_code'] ?? '' ),
				'region'       => $record['subdivisions'][0]['names']['en'] ?? '',
				'city'         => $record['city']['names']['en'] ?? '',
			);
		} catch ( Exception $e ) {
			return null;
		}
	}

	// -------------------------------------------------------------------------
	// MaxMind database download (admin AJAX)
	// -------------------------------------------------------------------------

	/**
	 * Downloads and extracts the MaxMind GeoLite2-City database.
	 * Registered on wp_ajax_wp_pop_download_maxmind_db.
	 *
	 * @return void  Sends a JSON response and exits.
	 */
	public static function handle_download_maxmind_db() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-pop' ) ) );
		}

		check_ajax_referer( 'wp_pop_download_maxmind_db', 'nonce' );

		$license_key = sanitize_text_field( get_option( 'wp_pop_maxmind_license_key', '' ) );
		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Please save your MaxMind license key first, then click Download.', 'wp-pop' ) ) );
		}

		$url = add_query_arg(
			array(
				'edition_id'  => 'GeoLite2-City',
				'license_key' => $license_key,
				'suffix'      => 'tar.gz',
			),
			'https://download.maxmind.com/app/geoip_download'
		);

		$temp_file = get_temp_dir() . 'wp-pop-geolite2-' . time() . '.tar.gz';
		$response  = wp_remote_get(
			$url,
			array(
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $temp_file,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $code ) {
			@unlink( $temp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			wp_send_json_error( array( 'message' => __( 'Invalid MaxMind license key (HTTP 401). Please check the key and try again.', 'wp-pop' ) ) );
		}

		if ( 200 !== $code ) {
			@unlink( $temp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			wp_send_json_error(
				array(
					/* translators: %d: HTTP status code */
					'message' => sprintf( __( 'Download failed (HTTP %d). Please try again.', 'wp-pop' ), $code ),
				)
			);
		}

		// Extract the .mmdb file from the tar.gz archive.
		$upload_dir = wp_upload_dir();
		$dest_dir   = trailingslashit( $upload_dir['basedir'] ) . 'wp-pop/';
		wp_mkdir_p( $dest_dir );

		$mmdb_path = self::extract_mmdb_from_tar( $temp_file, $dest_dir );
		@unlink( $temp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if ( ! $mmdb_path ) {
			wp_send_json_error( array( 'message' => __( 'Could not extract the .mmdb file from the archive. Ensure the PharData (zlib) PHP extension is available on your server.', 'wp-pop' ) ) );
		}

		update_option( 'wp_pop_maxmind_db_path', $mmdb_path );
		update_option( 'wp_pop_maxmind_db_updated', current_time( 'mysql' ) );
		self::purge_cache();

		wp_send_json_success(
			array(
				/* translators: %s: file name */
				'message' => sprintf( __( 'Database saved (%s). Geo cache cleared.', 'wp-pop' ), esc_html( basename( $mmdb_path ) ) ),
			)
		);
	}

	/**
	 * Extracts the first *.mmdb file found inside a .tar.gz archive.
	 *
	 * @param  string $tar_gz_path  Full path to the downloaded archive.
	 * @param  string $dest_dir     Directory to write the extracted file into.
	 * @return string|false         Absolute path to the .mmdb file, or false on failure.
	 */
	private static function extract_mmdb_from_tar( $tar_gz_path, $dest_dir ) {
		if ( ! class_exists( 'PharData' ) ) {
			return false;
		}

		try {
			$phar = new PharData( $tar_gz_path );
			foreach ( new RecursiveIteratorIterator( $phar ) as $file ) {
				if ( 'mmdb' === strtolower( pathinfo( $file->getFileName(), PATHINFO_EXTENSION ) ) ) {
					$dest = $dest_dir . basename( $file->getFileName() );
					copy( $file->getPathname(), $dest );
					return $dest;
				}
			}
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
