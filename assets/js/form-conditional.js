( function () {
	'use strict';

	// Vanilla JS, no jQuery dependency — this loads on the public frontend
	// (unlike assets/admin/*.js), so it must not assume jQuery is present.

	function fieldValues( form, name ) {
		var els = form.querySelectorAll( '[name="' + name + '"], [name="' + name + '[]"]' );
		var values = [];

		els.forEach( function ( el ) {
			if ( 'radio' === el.type || 'checkbox' === el.type ) {
				if ( el.checked ) {
					values.push( el.value );
				}
				return;
			}
			if ( 'select-multiple' === el.type ) {
				Array.prototype.forEach.call( el.selectedOptions || [], function ( opt ) {
					values.push( opt.value );
				} );
				return;
			}
			if ( el.value ) {
				values.push( el.value );
			}
		} );

		return values;
	}

	function evaluateRow( row, form ) {
		var raw = row.getAttribute( 'data-am-conditional' );
		if ( ! raw ) {
			return;
		}

		var rule;
		try {
			rule = JSON.parse( raw );
		} catch ( e ) {
			return;
		}

		var actual  = fieldValues( form, rule.field );
		var matches = actual.indexOf( String( rule.value ) ) !== -1;
		var visible = 'is_not' === rule.operator ? ! matches : matches;

		row.style.display = visible ? '' : 'none';

		row.querySelectorAll( 'input, select, textarea' ).forEach( function ( input ) {
			if ( ! visible ) {
				if ( input.hasAttribute( 'required' ) && ! input.hasAttribute( 'data-am-was-required' ) ) {
					input.setAttribute( 'data-am-was-required', '1' );
					input.removeAttribute( 'required' );
				}
				input.disabled = true;
			} else {
				input.disabled = false;
				if ( input.hasAttribute( 'data-am-was-required' ) ) {
					input.setAttribute( 'required', 'required' );
					input.removeAttribute( 'data-am-was-required' );
				}
			}
		} );
	}

	function evaluateForm( form ) {
		form.querySelectorAll( '[data-am-conditional]' ).forEach( function ( row ) {
			evaluateRow( row, form );
		} );
	}

	function init() {
		document.querySelectorAll( 'form.am-form' ).forEach( function ( form ) {
			evaluateForm( form );
			form.addEventListener( 'input', function () {
				evaluateForm( form );
			} );
			form.addEventListener( 'change', function () {
				evaluateForm( form );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
