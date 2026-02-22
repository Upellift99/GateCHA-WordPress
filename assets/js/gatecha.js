/**
 * GateCHA CAPTCHA – Frontend validation script.
 *
 * Prevents form submission if the ALTCHA widget has not been verified.
 * Includes a MutationObserver for dynamic content (Elementor popups, etc.).
 *
 * @package GateCHA_CAPTCHA
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		requestAnimationFrame( function () {
			initWidgets();
			observeDynamicContent();
		} );
	} );

	function initWidgets() {
		var widgets = document.querySelectorAll( 'altcha-widget' );

		for ( var i = 0; i < widgets.length; i++ ) {
			initWidget( widgets[ i ] );
		}
	}

	function initWidget( el ) {
		var checkbox = el.querySelector( 'input[type="checkbox"]' );
		if ( checkbox ) {
			// Clear the internal checkbox name so it does not conflict with the
			// hidden payload field that the widget creates.
			checkbox.setAttribute( 'name', '' );
		}

		var form = el.closest( 'form' );
		if ( form && checkbox ) {
			form.addEventListener(
				'submit',
				function ( ev ) {
					var state = el.querySelector( '.altcha' );
					if (
						state &&
						state.getAttribute( 'data-state' ) !== 'verified' &&
						! checkbox.reportValidity()
					) {
						ev.preventDefault();
						ev.stopPropagation();
					}
				},
				true
			);
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
} )();
