( function () {
	'use strict';

	function initTabs( wrapper ) {
		var links  = wrapper.querySelectorAll( '.am-tab-nav__link' );
		var panels = wrapper.querySelectorAll( '.am-tab-panel' );

		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();

				var target = link.getAttribute( 'data-tab' );

				links.forEach( function ( l ) {
					l.classList.remove( 'nav-tab-active', 'is-active' );
				} );
				panels.forEach( function ( p ) {
					p.classList.remove( 'is-active' );
				} );

				link.classList.add( 'nav-tab-active', 'is-active' );

				var panel = wrapper.querySelector( '.am-tab-panel[data-tab="' + target + '"]' );
				if ( panel ) {
					panel.classList.add( 'is-active' );
				}
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.am-tabs' ).forEach( initTabs );
	} );
} )();
