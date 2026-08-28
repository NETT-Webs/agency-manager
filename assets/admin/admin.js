( function ( $ ) {
	'use strict';

	function renderPreview( $wrapper, ids ) {
		var $preview = $wrapper.find( '.am-media-preview' );
		$preview.empty();

		ids.forEach( function ( id ) {
			var attachment = wp.media.attachment( id );
			attachment.fetch().then( function () {
				var sizes = attachment.get( 'sizes' );
				var url   = ( sizes && sizes.thumbnail ) ? sizes.thumbnail.url : attachment.get( 'url' );
				$preview.append( '<span class="am-media-thumb"><img src="' + url + '" alt=""></span>' );
			} );
		} );
	}

	$( document ).on( 'click', '.am-media-select', function ( e ) {
		e.preventDefault();

		var $wrapper = $( this ).closest( '.am-media-picker' );
		var $input   = $wrapper.find( '.am-media-ids' );
		var multiple = '1' === String( $wrapper.data( 'multiple' ) );

		var i18n = window.amMediaPicker || {};

		var frame = wp.media( {
			title: multiple ? ( i18n.selectImagesTitle || 'Select Images' ) : ( i18n.selectFileTitle || 'Select File' ),
			multiple: multiple,
		} );

		frame.on( 'select', function () {
			var selection = frame.state().get( 'selection' ).toJSON();
			var ids       = selection.map( function ( item ) {
				return item.id;
			} );

			if ( ! multiple ) {
				ids = ids.slice( 0, 1 );
			}

			$input.val( ids.join( ',' ) );
			renderPreview( $wrapper, ids );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.am-media-clear', function ( e ) {
		e.preventDefault();

		var $wrapper = $( this ).closest( '.am-media-picker' );
		$wrapper.find( '.am-media-ids' ).val( '' );
		$wrapper.find( '.am-media-preview' ).empty();
	} );
} )( jQuery );
