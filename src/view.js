/**
 * WP Pop! Interactivity API frontend store.
 *
 * Handles all popup triggers, frequency logic, A/B variant selection,
 * form-submit conversion tracking, and event analytics.
 *
 * A/B testing: when a popup carries `abTestId` in its context, this store
 * reads/writes `localStorage` key `wp-pop-ab-{testId}` to pick and persist
 * a variant ('a' or 'b') for the visitor.  Only the selected variant's
 * triggers are registered; the other popup's dialog is left dormant.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

import { store, getContext } from '@wordpress/interactivity';

// Module-level map so dialog refs survive re-renders.
const popupRefs = new Map(); // popupId (string) -> HTMLDialogElement

// ---- Helpers ----------------------------------------------------------------

/**
 * Read/write local or session storage safely.
 */
function storageGet( key, useSession = false ) {
	try {
		return ( useSession ? sessionStorage : localStorage ).getItem( key );
	} catch {
		return null;
	}
}

function storageSet( key, value, useSession = false ) {
	try {
		( useSession ? sessionStorage : localStorage ).setItem( key, value );
	} catch {
		// Storage unavailable — silently ignore.
	}
}

/**
 * Track an event via admin-ajax.php.
 */
function trackEvent( popupId, variantId, eventType ) {
	if ( ! window.wpPop?.ajaxUrl || ! window.wpPop?.nonce ) return;

	const body = new FormData();
	body.append( 'action', 'wp_pop_track_event' );
	body.append( 'nonce', window.wpPop.nonce );
	body.append( 'popup_id', popupId );
	body.append( 'variant_id', variantId || '' );
	body.append( 'event_type', eventType );

	fetch( window.wpPop.ajaxUrl, {
		method: 'POST',
		body,
		credentials: 'same-origin',
		keepalive: true, // Survives page unload (e.g. form submit redirect).
	} ).catch( () => {} ); // Fire-and-forget.
}

/**
 * Match a URL against a simple glob pattern (* as wildcard).
 * Falls back to substring match on the pathname.
 */
function urlMatchesPattern( url, pattern ) {
	if ( ! pattern ) return false;
	const regex = new RegExp(
		'^' + pattern.replace( /[.+?^${}()|[\]\\]/g, '\\$&' ).replace( /\*/g, '.*' ) + '$',
		'i'
	);
	if ( regex.test( url ) ) return true;
	try {
		return new URL( url ).pathname.includes( pattern );
	} catch {
		return false;
	}
}

/**
 * Queue a popup for success-URL conversion checking.
 * Stored in sessionStorage so it persists across the form redirect.
 */
function addPendingConvert( popupId, variantId, successUrl ) {
	if ( ! successUrl ) return;
	try {
		const pending = JSON.parse( sessionStorage.getItem( 'wp-pop-pending-convert' ) || '[]' );
		if ( ! pending.some( ( e ) => e.popupId === popupId ) ) {
			pending.push( { popupId, variantId, successUrl } );
			sessionStorage.setItem( 'wp-pop-pending-convert', JSON.stringify( pending ) );
		}
	} catch { /* storage unavailable */ }
}

/**
 * On every page load, fire convert events for popups whose success URL
 * matches the current page URL.
 */
function checkPendingConversions() {
	try {
		const pending = JSON.parse( sessionStorage.getItem( 'wp-pop-pending-convert' ) || '[]' );
		if ( ! pending.length ) return;
		const currentUrl = window.location.href;
		const remaining  = [];
		for ( const entry of pending ) {
			if ( urlMatchesPattern( currentUrl, entry.successUrl ) ) {
				trackEvent( entry.popupId, entry.variantId || '', 'convert' );
			} else {
				remaining.push( entry );
			}
		}
		if ( remaining.length !== pending.length ) {
			sessionStorage.setItem( 'wp-pop-pending-convert', JSON.stringify( remaining ) );
		}
	} catch { /* storage unavailable */ }
}
/**
 * Check whether the popup should be shown given its frequency settings.
 * Returns true if the popup is allowed to show.
 */
function checkFrequency( popupId, frequency, retriggerMinutes ) {
	const storageKey = `wp-pop-shown-${ popupId }`;

	if ( 'always' === frequency ) {
		return true;
	}

	if ( 'once' === frequency ) {
		return ! storageGet( storageKey );
	}

	if ( 'session' === frequency ) {
		return ! storageGet( storageKey, true );
	}

	if ( 'daily' === frequency ) {
		const last = parseInt( storageGet( storageKey ) || '0', 10 );
		return Date.now() - last > 86400000;
	}

	if ( 'custom' === frequency && retriggerMinutes ) {
		const last = parseInt( storageGet( storageKey ) || '0', 10 );
		return Date.now() - last > retriggerMinutes * 60000;
	}

	return true;
}

/**
 * Record that the popup was shown for frequency tracking.
 */
function recordShown( popupId, frequency ) {
	const storageKey = `wp-pop-shown-${ popupId }`;
	if ( 'session' === frequency ) {
		storageSet( storageKey, '1', true );
	} else if ( 'once' === frequency ) {
		storageSet( storageKey, '1' );
	} else {
		storageSet( storageKey, String( Date.now() ) );
	}
}

/**
 * Open the dialog for a given popup.
 * Called from trigger callbacks — never from reactive context.
 */
function openPopup( popupId, ctx ) {
	const dialog = popupRefs.get( String( popupId ) );
	if ( ! dialog ) return;

	// Frequency check — skipped in test mode (admin preview).
	if ( ! ctx.testMode && ! checkFrequency( popupId, ctx.frequency, ctx.retriggerMinutes ) ) {
		return;
	}

	recordShown( popupId, ctx.frequency );
	dialog.showModal();
	// For A/B tests, track using the variant label ('a' or 'b'); otherwise ''.
	trackEvent( popupId, ctx.activeVariant || '', 'view' );
	// Queue success-URL conversion tracking if the popup has one configured.
	addPendingConvert( popupId, ctx.activeVariant || '', ctx.successUrl || '' );
}

/**
 * Set up all triggers for a single popup context.
 */
function setupTriggers( popupId, ctx, element ) {
	// ---- A/B test: popup-level variant selection ----------------------------
	// When this popup is part of an A/B test, only set up triggers for the
	// variant that was (or will be) assigned to this visitor.  The other
	// popup's HTML is in the DOM but its triggers are never registered so it
	// will never open.
	if ( ctx.abTestId ) {
		const abKey = `wp-pop-ab-${ ctx.abTestId }`;
		let selected = storageGet( abKey );
		if ( ! selected ) {
			// Weighted pick: Math.random() * 100 < weightA → 'a', else 'b'.
			selected = ( Math.random() * 100 ) < ( ctx.abWeightA ?? 50 ) ? 'a' : 'b';
			storageSet( abKey, selected );
		}
		if ( selected !== ctx.abVariant ) {
			// Not this popup's turn — leave it dormant.
			return;
		}
		// Expose the selected variant label for analytics tracking.
		ctx.activeVariant = selected;
	}

	const trigger        = ctx.trigger || 'time';
	const delay          = ( ctx.triggerDelay || 0 ) * 1000;
	const scrollThreshold= ctx.scrollThreshold || 50;
	const clickSelector  = ctx.clickSelector || '';
	const inactivitySec  = ( ctx.inactivitySeconds || 30 ) * 1000;
	const elemSelector   = ctx.elementSelector || '';

	// --- Time delay ----------------------------------------------------------
	if ( 'time' === trigger ) {
		setTimeout( () => openPopup( popupId, ctx ), delay );
	}

	// --- Scroll percentage ---------------------------------------------------
	if ( 'scroll' === trigger ) {
		const handler = () => {
			const scrolled = ( window.scrollY / ( document.documentElement.scrollHeight - window.innerHeight ) ) * 100;
			if ( scrolled >= scrollThreshold ) {
				window.removeEventListener( 'scroll', handler );
				openPopup( popupId, ctx );
			}
		};
		window.addEventListener( 'scroll', handler, { passive: true } );
	}

	// --- Exit intent ---------------------------------------------------------
	if ( 'exit_intent' === trigger ) {
		const handler = ( e ) => {
			if ( e.clientY <= 0 ) {
				document.removeEventListener( 'mouseleave', handler );
				openPopup( popupId, ctx );
			}
		};
		document.addEventListener( 'mouseleave', handler );
	}

	// --- Click on selector ---------------------------------------------------
	if ( 'click' === trigger && clickSelector ) {
		document.querySelectorAll( clickSelector ).forEach( ( el ) => {
			el.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				openPopup( popupId, ctx );
			} );
		} );
	}

	// --- Inactivity ----------------------------------------------------------
	if ( 'inactivity' === trigger ) {
		let timer;
		const reset = () => {
			clearTimeout( timer );
			timer = setTimeout( () => openPopup( popupId, ctx ), inactivitySec );
		};
		[ 'mousemove', 'keydown', 'scroll', 'touchstart' ].forEach( ( ev ) => {
			document.addEventListener( ev, reset, { passive: true } );
		} );
		reset();
	}

	// --- Element visibility --------------------------------------------------
	if ( 'element_visibility' === trigger && elemSelector ) {
		const target = document.querySelector( elemSelector );
		if ( target && 'IntersectionObserver' in window ) {
			const observer = new IntersectionObserver( ( entries ) => {
				if ( entries[ 0 ].isIntersecting ) {
					observer.disconnect();
					openPopup( popupId, ctx );
				}
			} );
			observer.observe( target );
		}
	}

	// --- WooCommerce: add to cart --------------------------------------------
	if ( 'wc_add_to_cart' === trigger ) {
		document.body.addEventListener( 'added_to_cart', () => openPopup( popupId, ctx ) );
	}

	// --- WooCommerce: cart abandonment (session storage on unload) -----------
	if ( 'wc_cart_abandonment' === trigger ) {
		window.addEventListener( 'beforeunload', () => {
			if ( document.querySelector( '.woocommerce-cart-form' ) ) {
				storageSet( `wp-pop-cart-abandon-${ popupId }`, '1' );
			}
		} );
		// Fire on next visit if cart was abandoned.
		if ( storageGet( `wp-pop-cart-abandon-${ popupId }` ) ) {
			storageSet( `wp-pop-cart-abandon-${ popupId }`, null );
			setTimeout( () => openPopup( popupId, ctx ), delay || 1000 );
		}
	}

	// --- Form submit: track any form submission inside the popup as convert --
	element.addEventListener( 'submit', ( e ) => {
		if ( e.target.closest( 'dialog' ) ) {
			trackEvent( popupId, ctx.activeVariant || '', 'convert' );
		}
	}, { capture: true } );
}

// ---- Store ------------------------------------------------------------------

const { actions, callbacks } = store( 'wp-pop', {
	actions: {
		/**
		 * Close the active dialog.
		 */
		close() {
			const ctx    = getContext();
			const dialog = popupRefs.get( String( ctx.popupId ) );
			if ( dialog ) {
				dialog.close();
				trackEvent( ctx.popupId, ctx.activeVariant || '', 'dismiss' );
			}
		},

		/**
		 * Backdrop click: close only when clicking outside the inner content box.
		 */
		handleBackdropClick( event ) {
			const ctx    = getContext();
			const dialog = popupRefs.get( String( ctx.popupId ) );
			if ( ! dialog ) return;
			if ( ! ctx.closeOnOutsideClick ) return;

			// The dialog element covers the viewport; a click directly on <dialog>
			// means outside the content box.
			if ( event.target === dialog ) {
				dialog.close();
				trackEvent( ctx.popupId, ctx.activeVariant || '', 'dismiss' );
			}
		},

	/**
	 * Track a CTA link click.
	 */
	trackClick() {
		const ctx = getContext();
		trackEvent( ctx.popupId, ctx.activeVariant || '', 'click' );
	},
	},

	callbacks: {
		/**
		 * Runs via data-wp-run on the popup container.
		 * Stores dialog ref and sets up triggers.
		 *
		 * NOTE: getElement().ref is null during the preact hydration pre-commit
		 * phase when data-wp-run fires.  Look up the dialog by its known HTML id
		 * instead of using ref.querySelector so this callback never throws.
		 */
		initPopup() {
			const ctx = getContext();

			if ( ! ctx.popupId ) return;

			// Locate the dialog by its deterministic id (e.g. "wp-pop-6").
			const dialog = document.getElementById( 'wp-pop-' + ctx.popupId );
			if ( ! dialog ) return;

			popupRefs.set( String( ctx.popupId ), dialog );

			// Guard against double-initialisation (bootstrapExistingPopups may
			// have already run before the Interactivity API's init()).
			const container = dialog.closest( '[data-wp-interactive="wp-pop"]' );
			if ( ! container || container.dataset.wpPopInit ) return;
			container.dataset.wpPopInit = '1';

			setupTriggers( ctx.popupId, ctx, container );
		},
	},
} );

// ---------------------------------------------------------------------------
// Vanilla-JS fallback bootstrap
// ---------------------------------------------------------------------------
// If the Interactivity API's init() ran before store('wp-pop') was registered
// (timing edge case), data-wp-run="callbacks.initPopup" would be silently
// skipped.  This fallback scans the DOM after store() and bootstraps any
// popup elements that weren't initialised by the API.
function bootstrapExistingPopups() {
	document.querySelectorAll( '[data-wp-interactive="wp-pop"]' ).forEach( ( el ) => {
		if ( el.dataset.wpPopInit ) return; // already initialised by the API

		let ctx = {};
		try {
			ctx = JSON.parse( el.getAttribute( 'data-wp-context' ) || '{}' );
		} catch {
			return;
		}

		if ( ! ctx.popupId ) return;

		const dialog = el.querySelector( 'dialog' );
		if ( dialog ) popupRefs.set( String( ctx.popupId ), dialog );

		el.dataset.wpPopInit = '1';
		setupTriggers( ctx.popupId, ctx, el );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', bootstrapExistingPopups );
} else {
	bootstrapExistingPopups();
}

// Check for success-URL conversions from a popup shown on a previous page.
checkPendingConversions();
