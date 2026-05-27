'use strict';

(function () {
	/**
	 * Check whether a popup should be shown based on its frequency setting.
	 *
	 * @param {string} id       Popup identifier (e.g. "wp-pop-42").
	 * @param {string} frequency  always | session | daily | weekly | once
	 * @returns {boolean}
	 */
	function shouldShow(id, frequency) {
		if ('always' === frequency) {
			return true;
		}

		var key = 'wp_pop_shown_' + id;

		if ('session' === frequency) {
			return !sessionStorage.getItem(key);
		}

		var stored = localStorage.getItem(key);
		if (!stored) {
			return true;
		}

		if ('once' === frequency) {
			return false;
		}

		var storedTime = parseInt(stored, 10);
		var elapsed    = Date.now() - storedTime;

		if ('daily' === frequency) {
			return elapsed > 86400000;
		}

		if ('weekly' === frequency) {
			return elapsed > 604800000;
		}

		return true;
	}

	/**
	 * Record that a popup has been shown so frequency limits are respected.
	 *
	 * @param {string} id
	 * @param {string} frequency
	 */
	function markShown(id, frequency) {
		if ('always' === frequency) {
			return;
		}

		var key = 'wp_pop_shown_' + id;

		if ('session' === frequency) {
			sessionStorage.setItem(key, '1');
		} else if ('once' === frequency) {
			localStorage.setItem(key, '1');
		} else {
			// daily / weekly — store current timestamp.
			localStorage.setItem(key, Date.now().toString());
		}
	}

	// Auto-show CPT-based popups.
	document.querySelectorAll('.wp-pop[data-wp-pop-id]').forEach(function (container) {
		var popupId         = container.dataset.wpPopId;
		var frequency       = container.dataset.wpPopFrequency || 'session';
		var trigger         = container.dataset.wpPopTrigger || 'time';
		var scrollThreshold = parseInt(container.dataset.wpPopScrollThreshold, 10) || 0;
		var delay           = parseInt(container.dataset.wpPopDelay, 10) || 0;
		var dialog          = container.querySelector('.wp-pop__dialog');
		var openerEl        = null;
		var triggered       = false;

		if (!dialog || !popupId || !shouldShow(popupId, frequency)) {
			return;
		}

		// Restore focus to whatever had focus before the popup opened.
		dialog.addEventListener('close', function () {
			if (openerEl) {
				openerEl.focus();
				openerEl = null;
			}
		});

		function openPopup() {
			if (triggered) {
				return;
			}
			triggered = true;

			setTimeout(function () {
				openerEl = document.activeElement;
				dialog.showModal();
				// Move focus into the dialog for immediate keyboard access.
				var closeBtn = dialog.querySelector('.wp-pop__close');
				if (closeBtn) {
					closeBtn.focus();
				}
				markShown(popupId, frequency);

				// Track the view.
				if (window.wpPopAjax && container.dataset.wpPopPostId) {
					var formData = new FormData();
					formData.append('action', 'wp_pop_track_view');
					formData.append('popup_id', container.dataset.wpPopPostId);
					formData.append('nonce', window.wpPopAjax.nonce);
					fetch(window.wpPopAjax.url, { method: 'POST', body: formData });
				}
			}, delay * 1000);
		}

		if ('scroll' === trigger) {
			function onScroll() {
				var scrolled  = window.scrollY || window.pageYOffset;
				var docHeight = document.documentElement.scrollHeight - window.innerHeight;
				if (docHeight <= 0) {
					openPopup();
					window.removeEventListener('scroll', onScroll);
					return;
				}
				var pct = (scrolled / docHeight) * 100;
				if (pct >= scrollThreshold) {
					openPopup();
					window.removeEventListener('scroll', onScroll);
				}
			}
			window.addEventListener('scroll', onScroll, { passive: true });

		} else if ('interaction' === trigger) {
			var interactionEvents = ['mousedown', 'keydown', 'touchstart'];
			function onInteraction() {
				openPopup();
				interactionEvents.forEach(function (evt) {
					document.removeEventListener(evt, onInteraction, true);
				});
			}
			interactionEvents.forEach(function (evt) {
				document.addEventListener(evt, onInteraction, { once: true, capture: true });
			});

		} else if ('exit_intent' === trigger) {
			function onMouseLeave(event) {
				if (event.clientY <= 0) {
					openPopup();
					document.removeEventListener('mouseleave', onMouseLeave);
				}
			}
			document.addEventListener('mouseleave', onMouseLeave);

		} else {
			// Default: time trigger.
			openPopup();
		}

		// Close button.
		var closeBtn = container.querySelector('.wp-pop__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				dialog.close();
			});
		}

		// Clicks on ::backdrop register as clicks on the dialog element itself.
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) {
				dialog.close();
			}
		});
	});

	// ESC key is handled natively by <dialog> — no extra listener needed.
}());
