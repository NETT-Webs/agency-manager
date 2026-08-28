( function () {
	'use strict';

	function initCarousel( el ) {
		var track    = el.querySelector( '.am-carousel__track' );
		var prevBtn  = el.querySelector( '.am-carousel__prev' );
		var nextBtn  = el.querySelector( '.am-carousel__next' );
		var autoplay = '1' === el.getAttribute( 'data-autoplay' );
		var timer    = null;

		if ( ! track ) {
			return;
		}

		function scrollByAmount( direction ) {
			var item   = track.firstElementChild;
			var amount = item ? item.getBoundingClientRect().width + 20 : track.clientWidth;
			track.scrollBy( { left: direction * amount, behavior: 'smooth' } );
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				scrollByAmount( -1 );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				scrollByAmount( 1 );
			} );
		}

		if ( autoplay ) {
			timer = setInterval( function () {
				var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
				if ( atEnd ) {
					track.scrollTo( { left: 0, behavior: 'smooth' } );
				} else {
					scrollByAmount( 1 );
				}
			}, 5000 );

			el.addEventListener( 'mouseenter', function () {
				clearInterval( timer );
			} );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.am-carousel' ).forEach( initCarousel );
	} );
} )();
