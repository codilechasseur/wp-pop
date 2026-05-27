<?php

/**
 * Default template for a single popup.
 *
 * Theme authors can override this template by copying it to either:
 *   - {theme}/wp-pop/popup.php          (overrides ALL popups)
 *   - {theme}/wp-pop/popup-{id}.php     (overrides one specific popup)
 *
 * Available variables (provided by Wp_Pop_Public::render_popup()):
 *   @var WP_Post $popup              The popup post object.
 *   @var int     $id                 The popup post ID.
 *   @var string  $popup_element_id   HTML id attribute value, e.g. "wp-pop-42".
 *   @var array   $context            Full Interactivity API context array.
 *   @var string  $content            Popup body content (post_content filtered).
 *   @var string  $css_vars           Inline CSS custom properties for appearance.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$popup_type        = $context['popupType'] ?? 'modal';
$animation         = $context['animation'] ?? 'fade';
$show_close_button = $context['showCloseButton'] ?? true;
?>
<div
	class="wp-pop wp-pop--<?php echo esc_attr( $popup_type ); ?> wp-pop--anim-<?php echo esc_attr( $animation ); ?>"
	style="<?php echo esc_attr( $css_vars ); ?>"
	data-wp-interactive="wp-pop"
	data-wp-context='<?php echo wp_json_encode( $context, JSON_HEX_APOS | JSON_HEX_QUOT ); ?>'
	data-wp-run="callbacks.initPopup"
>
	<dialog
		class="wp-pop__dialog"
		id="<?php echo esc_attr( $popup_element_id ); ?>"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $popup_element_id ); ?>-title"
		data-wp-on--click="actions.handleBackdropClick"
	>
		<div class="wp-pop__inner">
			<?php if ( $show_close_button ) : ?>
			<button
				class="wp-pop__close"
				type="button"
				aria-label="<?php esc_attr_e( 'Close popup', 'wp-pop' ); ?>"
				data-wp-on--click="actions.close"
			>&times;</button>
			<?php endif; ?>

			<h2 class="wp-pop__title" id="<?php echo esc_attr( $popup_element_id ); ?>-title">
				<?php echo esc_html( get_the_title( $popup ) ); ?>
			</h2>

			<div class="wp-pop__content">
				<?php echo wp_kses_post( $content ); ?>
			</div>
		</div>
	</dialog>
</div>
