/**
 * GateCHA CAPTCHA – Frontend script.
 *
 * Responsibilities:
 *   1. Register the localized ALTCHA v3 i18n strings (provided by PHP via
 *      wp_localize_script as window.gatechaI18n) into ALTCHA's global i18n
 *      store. ALTCHA v3 dropped the per-widget `strings` attribute, so custom
 *      translations are now applied through the store.
 *   2. Prevent form submission until the ALTCHA widget has been verified, with a
 *      MutationObserver for dynamic content (Elementor popups, etc.).
 *
 * @package GateCHA_CAPTCHA
 */
( function () {
	'use strict';

	/* ------------------------------------------------------------------
	 * 1. ALTCHA v3 i18n registration
	 * ---------------------------------------------------------------- */

	/**
	 * Register the localized strings into ALTCHA's global i18n store.
	 *
	 * @return {boolean} True when done (registered, or nothing to register);
	 *                   false when the ALTCHA module is not ready yet.
	 */
	function registerI18n() {
		var data = window.gatechaI18n;
		if ( ! data || ! data.strings ) {
			return true;
		}
		var altcha = window.$altcha;
		if ( altcha && altcha.i18n && typeof altcha.i18n.set === 'function' ) {
			altcha.i18n.set( data.language || 'en', data.strings );
			return true;
		}
		return false;
	}

	/**
	 * Call fn now; if it returns false, poll it until it succeeds or we run out
	 * of tries. ALTCHA loads as an async module, so $altcha may not exist yet
	 * when this script runs. The i18n store is reactive, so a late registration
	 * still updates an already-mounted widget.
	 *
	 * @param {Function} fn       Returns true when satisfied.
	 * @param {number}   maxTries Maximum poll attempts (100ms apart).
	 */
	function whenReady( fn, maxTries ) {
		if ( fn() ) {
			return;
		}
		var tries = 0;
		var timer = setInterval( function () {
			tries++;
			if ( fn() || tries >= maxTries ) {
				clearInterval( timer );
			}
		}, 100 );
	}

	/* ------------------------------------------------------------------
	 * 2. Widget init: submit guard
	 * ---------------------------------------------------------------- */

	function initWidget( el ) {
		// Bind each widget's listeners only once, even if the MutationObserver
		// fires repeatedly on re-render.
		if ( el.getAttribute( 'data-gatecha-init' ) ) {
			return;
		}
		el.setAttribute( 'data-gatecha-init', '1' );

		var form = el.closest( 'form' );
		if ( ! form ) {
			return;
		}

		// Block submission until the widget reports a verified state. The widget
		// mounts asynchronously (ALTCHA v3 is an async ES module), so we look up
		// its state and checkbox at submit time rather than at init time.
		form.addEventListener(
			'submit',
			function ( ev ) {
				var state = el.querySelector( '.altcha' );
				var checkbox = el.querySelector( 'input[type="checkbox"]' );
				if (
					state &&
					state.getAttribute( 'data-state' ) !== 'verified' &&
					checkbox &&
					! checkbox.reportValidity()
				) {
					ev.preventDefault();
					ev.stopPropagation();
				}
			},
			true
		);
	}

	function initWidgets() {
		var widgets = document.querySelectorAll( 'altcha-widget' );
		for ( var i = 0; i < widgets.length; i++ ) {
			initWidget( widgets[ i ] );
		}
	}

	/**
	 * Watch for dynamically inserted widgets (e.g. Elementor popups) and
	 * remove duplicate .altcha elements that can appear on re-render.
	 */
	function observeDynamicContent() {
		if ( typeof MutationObserver === 'undefined' ) {
			return;
		}

		var observer = new MutationObserver( function () {
			var widgets = document.querySelectorAll( 'altcha-widget' );
			for ( var i = 0; i < widgets.length; i++ ) {
				initWidget( widgets[ i ] );
				var altchas = widgets[ i ].querySelectorAll( '.altcha' );
				if ( altchas.length > 1 ) {
					for ( var j = 0; j < altchas.length - 1; j++ ) {
						altchas[ j ].remove();
					}
				}
			}
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
	}

	/* ------------------------------------------------------------------
	 * Bootstrap
	 * ---------------------------------------------------------------- */

	document.addEventListener( 'DOMContentLoaded', function () {
		whenReady( registerI18n, 50 );
		requestAnimationFrame( function () {
			initWidgets();
			observeDynamicContent();
		} );
	} );
} )();
