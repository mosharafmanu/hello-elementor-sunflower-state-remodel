/**
 * Symptom checker — recomputes the verdict panel from the tally of checked
 * symptoms. Thresholds match the design source: 0-2 low, 3-4 mid, 5+ high.
 */
( function () {
	'use strict';

	function bandFor( count ) {
		if ( count >= 5 ) {
			return 'high';
		}
		if ( count >= 3 ) {
			return 'mid';
		}
		return 'low';
	}

	function init( root ) {
		if ( root.dataset.ssrScReady ) {
			return;
		}
		root.dataset.ssrScReady = '1';

		var config;
		try {
			config = JSON.parse( root.dataset.config );
		} catch ( e ) {
			return;
		}

		var inputs = root.querySelectorAll( '.ssr-sc-input' );
		var verdict = root.querySelector( '.ssr-sc-verdict' );
		var title = root.querySelector( '.ssr-sc-title' );
		var desc = root.querySelector( '.ssr-sc-desc' );
		var num = root.querySelector( '.ssr-sc-num' );
		var cta = root.querySelector( '.ssr-sc-cta' );

		function update() {
			var count = 0;
			inputs.forEach( function ( input ) {
				if ( input.checked ) {
					count++;
				}
				input.closest( '.ssr-sc-item' ).classList.toggle( 'is-checked', input.checked );
			} );

			var band = config.verdicts[ bandFor( count ) ];
			verdict.style.setProperty( '--ssr-sc-color', band.color );
			title.textContent = band.title;
			desc.textContent = band.desc;
			num.textContent = count;

			if ( cta ) {
				cta.hidden = count < 3;
			}
		}

		inputs.forEach( function ( input ) {
			input.addEventListener( 'change', update );
		} );

		update();
	}

	function initAll( scope ) {
		( scope || document ).querySelectorAll( '.ssr-sc' ).forEach( init );
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
				'frontend/element_ready/ssr-symptom-checker.default',
				function ( $scope ) {
					initAll( $scope[ 0 ] );
				}
			);
		}
	} );
} )();
