'use strict';

(function ($) {
	// Toggle the specific-pages list when the display scope radio changes.
	$('input[name="wp_pop_display_scope"]').on('change', function () {
		if ('specific' === this.value) {
			$('#wp-pop-specific-pages').show();
		} else {
			$('#wp-pop-specific-pages').hide();
		}
	});

	// Toggle the schedule date fields when the "Enable scheduling" checkbox changes.
	$(document).on('change', '#wp_pop_scheduling_enabled', function () {
		if (this.checked) {
			$('#wp-pop-schedule-dates').show();
		} else {
			$('#wp-pop-schedule-dates').hide();
		}
	});

	// Toggle scroll-threshold row when the trigger radio changes (meta box).
	$(document).on('change', 'input[name="wp_pop_trigger"]', function () {
		if ('scroll' === this.value) {
			$('#wp-pop-scroll-threshold-wrap').show();
		} else {
			$('#wp-pop-scroll-threshold-wrap').hide();
		}
	});

	// Toggle scroll-threshold row when the trigger radio changes (settings page).
	$(document).on('change', 'input[name="wp_pop_default_trigger"]', function () {
		if ('scroll' === this.value) {
			$('#wp-pop-default-scroll-threshold-wrap').show();
		} else {
			$('#wp-pop-default-scroll-threshold-wrap').hide();
		}
	});

	// Toggle custom width input when the width select changes (meta box).
	$(document).on('change', '#wp_pop_width_preset', function () {
		if ('custom' === this.value) {
			$('#wp-pop-custom-width-wrap').show();
		} else {
			$('#wp-pop-custom-width-wrap').hide();
		}
	});

	// Toggle custom width input when the width select changes (settings page).
	$(document).on('change', '#wp_pop_default_width_preset', function () {
		if ('custom' === this.value) {
			$('#wp-pop-default-custom-width-wrap').show();
		} else {
			$('#wp-pop-default-custom-width-wrap').hide();
		}
	});
}(jQuery));
