/**
 * Dependency-free copy-to-clipboard, search filter, and live shortcode
 * builder for the Shortcode Reference panel (same philosophy as
 * tabs.js/carousel.js — no build step, no library).
 */
( function () {
	'use strict';

	function showToast( button ) {
		var text = ( window.amShortcodeReference && window.amShortcodeReference.copiedText ) || 'Shortcode copied.';
		var toast = document.createElement( 'span' );
		toast.className = 'am-shortcode-copy__toast';
		toast.textContent = text;
		button.insertAdjacentElement( 'afterend', toast );
		window.setTimeout( function () {
			toast.remove();
		}, 1600 );
	}

	function copy( value, button ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( value ).then( function () {
				showToast( button );
			} );
			return;
		}

		// Fallback for browsers without the async Clipboard API.
		var textarea = document.createElement( 'textarea' );
		textarea.value = value;
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		document.body.appendChild( textarea );
		textarea.select();
		try {
			document.execCommand( 'copy' );
			showToast( button );
		} catch ( e ) {
			// Silently ignore — clipboard access denied is not actionable here.
		}
		textarea.remove();
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.am-shortcode-copy' );
		if ( ! button ) {
			return;
		}
		event.preventDefault();
		copy( button.getAttribute( 'data-shortcode' ) || '', button );
	} );

	/**
	 * Shortcode Builder: reads every field inside a .am-shortcode-builder
	 * container and rebuilds the [tag param="value" ...] string live,
	 * skipping any field left at its default/empty value.
	 */
	function buildShortcode( builder ) {
		var tag = builder.getAttribute( 'data-tag' );
		var parts = [ tag ];

		builder.querySelectorAll( '[data-param]' ).forEach( function ( field ) {
			var param = field.getAttribute( 'data-param' );
			var value = '';

			if ( 'checkbox' === field.type ) {
				value = field.checked ? '1' : '';
			} else {
				value = field.value || '';
			}

			if ( '' === value ) {
				return;
			}

			parts.push( param + '="' + value.replace( /"/g, '&quot;' ) + '"' );
		} );

		return '[' + parts.join( ' ' ) + ']';
	}

	function refreshBuilder( builder ) {
		var shortcode = buildShortcode( builder );
		var output = builder.querySelector( '.am-shortcode-builder__output' );
		if ( ! output ) {
			return;
		}
		output.querySelector( '.am-shortcode-builder__code' ).textContent = shortcode;
		output.querySelector( '.am-shortcode-copy' ).setAttribute( 'data-shortcode', shortcode );
	}

	document.addEventListener( 'input', function ( event ) {
		var builder = event.target.closest( '.am-shortcode-builder' );
		if ( builder ) {
			refreshBuilder( builder );
		}
	} );

	document.addEventListener( 'change', function ( event ) {
		var builder = event.target.closest( '.am-shortcode-builder' );
		if ( builder ) {
			refreshBuilder( builder );
		}
	} );

	/**
	 * Search filter: hides any .am-shortcode-reference__item whose tag or
	 * description doesn't contain the typed text (case-insensitive
	 * substring match against the pre-lowercased data-search-text
	 * attribute), and shows a "no results" message if everything is hidden.
	 */
	document.addEventListener( 'input', function ( event ) {
		if ( ! event.target.classList.contains( 'am-shortcode-search' ) ) {
			return;
		}

		var panel = event.target.closest( '.am-shortcode-reference' );
		if ( ! panel ) {
			return;
		}

		var term = event.target.value.trim().toLowerCase();
		var anyVisible = false;

		panel.querySelectorAll( '.am-shortcode-reference__item' ).forEach( function ( item ) {
			var haystack = item.getAttribute( 'data-search-text' ) || '';
			var visible = '' === term || haystack.indexOf( term ) !== -1;
			item.hidden = ! visible;
			if ( visible ) {
				anyVisible = true;
			}
		} );

		panel.querySelectorAll( '.am-shortcode-reference__group' ).forEach( function ( heading ) {
			var next = heading.nextElementSibling;
			var groupHasVisible = false;
			while ( next && ! next.classList.contains( 'am-shortcode-reference__group' ) ) {
				if ( next.classList.contains( 'am-shortcode-reference__item' ) && ! next.hidden ) {
					groupHasVisible = true;
				}
				next = next.nextElementSibling;
			}
			heading.hidden = ! groupHasVisible;
		} );

		var noResults = panel.querySelector( '.am-shortcode-reference__no-results' );
		if ( noResults ) {
			noResults.hidden = anyVisible || '' === term;
		}
	} );
} )();
