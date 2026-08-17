/**
 * Before / After comparison slider behaviour.
 *
 * Pointer drag, click-to-position, and full keyboard support (arrows / Home /
 * End) so the control is operable without a mouse.
 */
( function () {
	'use strict';

	function setPosition( el, pct ) {
		var clamped = Math.max( 0, Math.min( 100, pct ) );
		el.style.setProperty( '--ssr-ba-pos', clamped + '%' );
		var handle = el.querySelector( '.ssr-ba-handle' );
		if ( handle ) {
			handle.setAttribute( 'aria-valuenow', Math.round( clamped ) );
		}
	}

	function positionFromEvent( el, clientX ) {
		var rect = el.getBoundingClientRect();
		if ( ! rect.width ) {
			return 50;
		}
		return ( ( clientX - rect.left ) / rect.width ) * 100;
	}

	function init( el ) {
		if ( el.dataset.ssrBaReady ) {
			return;
		}
		el.dataset.ssrBaReady = '1';
		setPosition( el, 50 );

		var dragging = false;
		var handle = el.querySelector( '.ssr-ba-handle' );

		el.addEventListener( 'pointerdown', function ( e ) {
			dragging = true;
			el.setPointerCapture( e.pointerId );
			setPosition( el, positionFromEvent( el, e.clientX ) );
		} );

		el.addEventListener( 'pointermove', function ( e ) {
			if ( ! dragging ) {
				return;
			}
			setPosition( el, positionFromEvent( el, e.clientX ) );
		} );

		[ 'pointerup', 'pointercancel', 'pointerleave' ].forEach( function ( evt ) {
			el.addEventListener( evt, function () {
				dragging = false;
			} );
		} );

		if ( handle ) {
			handle.addEventListener( 'keydown', function ( e ) {
				var current = parseFloat( el.style.getPropertyValue( '--ssr-ba-pos' ) ) || 50;
				var step = e.shiftKey ? 10 : 2;
				var next = null;

				if ( 'ArrowLeft' === e.key || 'ArrowDown' === e.key ) {
					next = current - step;
				} else if ( 'ArrowRight' === e.key || 'ArrowUp' === e.key ) {
					next = current + step;
				} else if ( 'Home' === e.key ) {
					next = 0;
				} else if ( 'End' === e.key ) {
					next = 100;
				}

				if ( null !== next ) {
					e.preventDefault();
					setPosition( el, next );
				}
			} );
		}
	}

	function initAll( root ) {
		( root || document ).querySelectorAll( '.ssr-ba' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	// Re-init inside the Elementor editor preview.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/ssr-before-after.default',
				function ( $scope ) {
					initAll( $scope[ 0 ] );
				}
			);
		}
	} );
} )();
