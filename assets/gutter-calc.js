/**
 * Gutter sizing calculator — the arithmetic model from src/pages/Gutters.tsx.
 *   pitchFactor  low 0.9 | medium 1.0 | steep 1.15
 *   needsSix     sqft * pitchFactor > 1400
 *   linearFeet   round(sqrt(sqft) * 4.2)
 *   downspouts   ceil(linearFeet / 37)
 */
( function () {
	'use strict';

	var FACTORS = { low: 0.9, medium: 1.0, steep: 1.15 };

	function group( n ) {
		return String( n ).replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
	}

	function init( root ) {
		if ( root.dataset.ssrGcalcReady ) {
			return;
		}
		root.dataset.ssrGcalcReady = '1';

		var range = root.querySelector( '.ssr-gcalc-range' );
		var sqftOut = root.querySelector( '.ssr-gcalc-sqft' );
		var buttons = root.querySelectorAll( '.ssr-gcalc-pitch-btn' );
		var out = {};
		root.querySelectorAll( '[data-out]' ).forEach( function ( el ) {
			out[ el.dataset.out ] = el;
		} );

		var pitch = 'medium';

		function update() {
			var sqft = Number( range.value );
			var factor = FACTORS[ pitch ];
			var needsSix = sqft * factor > 1400;
			var linear = Math.round( Math.sqrt( sqft ) * 4.2 );
			var downspouts = Math.ceil( linear / 37 );

			sqftOut.textContent = group( sqft ) + ' sq ft';
			out.size.textContent = needsSix ? '6"' : '5"';
			out.downspouts.textContent = downspouts;
			out.linear.textContent = '~' + linear;

			out.verdict.textContent = needsSix
				? 'With ' + group( sqft ) + ' sq ft of roof at a ' + pitch +
					' pitch, your drainage load exceeds 1,400 — the threshold where ' +
					'5-inch gutters regularly overflow during intense Kansas storms. ' +
					'6-inch K-style handles the peak flow.'
				: 'With ' + group( sqft ) + ' sq ft at a ' + pitch +
					' pitch, 5-inch K-style gutters provide adequate capacity for ' +
					'typical Wichita storm intensity. Downspouts placed every 35–40 ' +
					'linear feet keep flow moving.';
		}

		range.addEventListener( 'input', update );

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				pitch = btn.dataset.pitch;
				buttons.forEach( function ( b ) {
					var on = b === btn;
					b.classList.toggle( 'is-active', on );
					b.setAttribute( 'aria-checked', on ? 'true' : 'false' );
				} );
				update();
			} );
		} );

		update();
	}

	function initAll( scope ) {
		( scope || document ).querySelectorAll( '.ssr-gcalc' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/ssr-gutter-calc.default',
				function ( $scope ) {
					initAll( $scope[ 0 ] );
				}
			);
		}
	} );
} )();
