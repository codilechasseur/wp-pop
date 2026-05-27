<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wp_pop_version' );
delete_option( 'wp_pop_default_frequency' );
delete_option( 'wp_pop_default_delay' );
