/**
 * GateCHA CAPTCHA – Frontend script.
 *
 * Responsibilities:
 *   1. Register the localized ALTCHA v3 i18n strings (provided by PHP via
 *      wp_localize_script as window.gatechaI18n) into ALTCHA's global i18n
 *      store. ALTCHA v3 dropped the per-widget `strings` attribute, so custom
 *      translations are now applied through the store.
 *   2. Collect a privacy-preserving Human Interaction Signature (HIS) and write
 *      the aggregate into the hidden `gatecha_his_signals` field on submit. Only
 *      aggregates are emitted (counts, total pointer distance, durations, timing
 *      variance) — never raw coordinates, timestamps, or key contents. Mirrors
 *      the server-side contract in web/src/lib/his.ts.
 *   3. Prevent form submission until the ALTCHA widget has been verified, with a
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
	 * 2. HIS collector (privacy-preserving aggregates)
	 * ---------------------------------------------------------------- */

	function stdevOfIntervals( times ) {
		if ( times.length < 2 ) {
			return 0;
		}
		var intervals = [];
		var i;
		for ( i = 1; i < times.length; i++ ) {
			intervals.push( times[ i ] - times[ i - 1 ] );
		}
		var sum = 0;
		for ( i = 0; i < intervals.length; i++ ) {
			sum += intervals[ i ];
		}
		var mean = sum / intervals.length;
		var variance = 0;
		for ( i = 0; i < intervals.length; i++ ) {
			variance += Math.pow( intervals[ i ] - mean, 2 );
		}
		return Math.sqrt( variance / intervals.length );
	}

	/**
	 * Start collecting interaction signals on target (defaults to the document).
	 * Returns an object exposing signals(), a snapshot of aggregates so far.
	 *
	 * @param {EventTarget} target Element/document to observe.
	 * @return {{signals: Function}}
	 */
	function startHISCollector( target ) {
		target = target || document;

		var hasPerf = !! ( window.performance && window.performance.now );
		var startTime = hasPerf ? window.performance.now() : Date.now();
		var elapsed = function () {
			return ( hasPerf ? window.performance.now() : Date.now() ) - startTime;
		};

		var firstAt = -1;
		var pointerEvents = 0;
		var pointerDistance = 0;
		var lastX = NaN;
		var lastY = NaN;
		var scrolls = 0;
		var touches = 0;
		var keydowns = 0;
		var keyTimes = [];

		function markFirst() {
			if ( firstAt < 0 ) {
				firstAt = elapsed();
			}
		}
		function onPointer( e ) {
			markFirst();
			pointerEvents++;
			if ( ! isNaN( lastX ) ) {
				pointerDistance += Math.sqrt(
					Math.pow( e.clientX - lastX, 2 ) + Math.pow( e.clientY - lastY, 2 )
				);
			}
			lastX = e.clientX;
			lastY = e.clientY;
		}
		function onScroll() {
			markFirst();
			scrolls++;
		}
		function onTouch() {
			markFirst();
			touches++;
		}
		function onKey() {
			markFirst();
			keydowns++;
			keyTimes.push( elapsed() );
		}

		var opts = { passive: true };
		target.addEventListener( 'pointermove', onPointer, opts );
		target.addEventListener( 'scroll', onScroll, { passive: true, capture: true } );
		target.addEventListener( 'touchstart', onTouch, opts );
		target.addEventListener( 'keydown', onKey, opts );

		return {
			signals: function () {
				return {
					duration_ms: Math.round( elapsed() ),
					time_to_first_ms: firstAt < 0 ? -1 : Math.round( firstAt ),
					pointer_events: pointerEvents,
					pointer_distance: Math.round( pointerDistance ),
					scrolls: scrolls,
					touches: touches,
					keydowns: keydowns,
					key_interval_stdev_ms: Math.round( stdevOfIntervals( keyTimes ) )
				};
			}
		};
	}

	var hisCollector = null;
	function getCollector() {
		if ( ! hisCollector ) {
			hisCollector = startHISCollector( document );
		}
		return hisCollector;
	}

	/* ------------------------------------------------------------------
	 * 3. Widget init: submit guard + HIS field write
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

		// Write the HIS aggregate into the hidden field right before submit, so
		// it travels with both native POST and AJAX (serialized) submissions.
		var hisField = form.querySelector( '.gatecha-his-signals' );
		if ( hisField && ! hisField.getAttribute( 'data-gatecha-his-bound' ) ) {
			hisField.setAttribute( 'data-gatecha-his-bound', '1' );
			form.addEventListener(
				'submit',
				function () {
					try {
						hisField.value = JSON.stringify( getCollector().signals() );
					} catch ( e ) {}
				},
				true
			);
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
		getCollector(); // Start collecting interaction signals as early as possible.
		requestAnimationFrame( function () {
			initWidgets();
			observeDynamicContent();
		} );
	} );
} )();
