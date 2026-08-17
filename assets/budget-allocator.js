/**
 * Kitchen budget allocator — recomputes every category's dollar figure and the
 * summary sentence from the total-budget slider. Model from
 * src/pages/Kitchen.tsx: alloc = round(total * pct / 100).
 */
( function () {
	'use strict';

	function money( n ) {
		return '$' + String( n ).replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
	}

	function init( root ) {
		if ( root.dataset.ssrAllocReady ) {
			return;
		}
		root.dataset.ssrAllocReady = '1';

		var config;
		try {
			config = JSON.parse( root.dataset.config );
		} catch ( e ) {
			return;
		}

		var range = root.querySelector( '.ssr-alloc-range' );
		var totalOut = root.querySelector( '.ssr-alloc-total-value' );
		var amounts = root.querySelectorAll( '.ssr-alloc-amount' );
		var summary = root.querySelector( '.ssr-alloc-summary' );

		function update() {
			var total = Number( range.value );
			var alloc = function ( pct ) {
				return Math.round( total * pct / 100 );
			};

			totalOut.textContent = money( total );

			amounts.forEach( function ( el ) {
				el.textContent = money( alloc( Number( el.dataset.pct ) ) );
			} );

			var text = config.summary.replace( '{total}', money( total ) );
			config.pcts.slice( 0, 4 ).forEach( function ( pct, i ) {
				text = text.replace( '{cat' + ( i + 1 ) + '}', money( alloc( pct ) ) );
			} );
			summary.textContent = text;
		}

		range.addEventListener( 'input', update );
		update();
	}

	function initAll( scope ) {
		( scope || document ).querySelectorAll( '.ssr-alloc' ).forEach( init );
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
				'frontend/element_ready/ssr-budget-allocator.default',
				function ( $scope ) {
					initAll( $scope[ 0 ] );
				}
			);
		}
	} );
} )();
