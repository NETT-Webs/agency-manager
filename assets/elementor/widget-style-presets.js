/**
 * "Widget Style" presets for Agency Manager's Elementor widgets — Save
 * Current / Load / Rename / Delete a named snapshot of every Style-tab
 * control's value, so a non-technical user only styles one widget once and
 * every other widget of any type can load the same look.
 *
 * Editor-only (enqueued via elementor/editor/after_enqueue_scripts — never
 * loaded on the public-facing site). The four BUTTON controls registered in
 * Base_Grid_Widget::register_style_controls() (widget_style_load/save/
 * rename/delete) fire the matching am:widget_style_* event on Elementor's
 * own editor event channel; nothing else triggers these.
 *
 * "Load" never calls the server — window.amWidgetStylePresets.presets
 * (localized once per editor page load in
 * Elementor_Integration::enqueue_editor_scripts()) already holds every
 * preset's full values, so applying one is instant. Save/Rename/Delete
 * persist via the three wp_ajax_am_*_widget_style_preset handlers in
 * Elementor\Widget_Style_Presets, then update both the local cache and the
 * live <select> so the change is visible without reloading the editor.
 */
( function ( $ ) {
	'use strict';

	// Kept in sync with reset-style-defaults.js's own META_CONTROLS list —
	// these manage presets themselves and are never part of a saved preset's
	// values, even though they live in Style-tab sections.
	var META_CONTROLS = [
		'reset_style_defaults',
		'widget_style_preset_select',
		'widget_style_preset_name',
		'widget_style_load',
		'widget_style_save',
		'widget_style_rename',
		'widget_style_delete'
	];

	function config() {
		return window.amWidgetStylePresets || { presets: {}, ajaxUrl: '', nonce: '', i18n: {} };
	}

	function text( key, fallback ) {
		return ( config().i18n && config().i18n[ key ] ) || fallback;
	}

	function getEditedElementView() {
		if ( ! window.elementor || ! elementor.getPanelView ) {
			return null;
		}
		var panel = elementor.getPanelView();
		var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
		return ( page && page.getOption && page.getOption( 'editedElementView' ) ) || null;
	}

	function getWidgetConfig( editedElementView ) {
		var widgetType = editedElementView.model.get( 'widgetType' ) || editedElementView.model.get( 'elType' );
		var widgetsCache = elementor.config && elementor.config.widgets;
		return widgetsCache && widgetsCache[ widgetType ];
	}

	function styleControlNames( widgetConfig ) {
		return Object.keys( widgetConfig.controls || {} ).filter( function ( name ) {
			var control = widgetConfig.controls[ name ];
			return control && 'style' === control.tab && META_CONTROLS.indexOf( name ) === -1;
		} );
	}

	function refreshSelectOptions( widgetConfig ) {
		var presetNames = Object.keys( config().presets );
		var options = { '': '— Select —' };
		presetNames.forEach( function ( name ) {
			options[ name ] = name;
		} );

		if ( widgetConfig.controls.widget_style_preset_select ) {
			widgetConfig.controls.widget_style_preset_select.options = options;
		}

		var select = document.querySelector( '.elementor-panel select[data-setting="widget_style_preset_select"]' );
		if ( select ) {
			var current = select.value;
			select.innerHTML = '';
			Object.keys( options ).forEach( function ( value ) {
				var option = document.createElement( 'option' );
				option.value = value;
				option.textContent = options[ value ];
				select.appendChild( option );
			} );
			if ( Object.prototype.hasOwnProperty.call( options, current ) ) {
				select.value = current;
			}
		}
	}

	function handleSave() {
		var editedElementView = getEditedElementView();
		if ( ! editedElementView ) {
			return;
		}

		var model = editedElementView.model;
		var widgetConfig = getWidgetConfig( editedElementView );
		if ( ! widgetConfig ) {
			return;
		}

		var name = ( model.getSetting( 'widget_style_preset_name' ) || '' ).toString().trim();
		if ( ! name ) {
			window.alert( text( 'nameRequired', 'Enter a preset name first.' ) ); // eslint-disable-line no-alert
			return;
		}

		var values = {};
		styleControlNames( widgetConfig ).forEach( function ( controlName ) {
			values[ controlName ] = model.getSetting( controlName );
		} );

		$.post( config().ajaxUrl, {
			action: 'am_save_widget_style_preset',
			nonce: config().nonce,
			name: name,
			values: JSON.stringify( values )
		} ).done( function ( response ) {
			if ( response && response.success ) {
				config().presets = response.data.presets;
				refreshSelectOptions( widgetConfig );
				model.setSetting( 'widget_style_preset_select', name );
				window.alert( text( 'saved', 'Widget style preset saved.' ) ); // eslint-disable-line no-alert
			} else {
				window.alert( ( response && response.data && response.data.message ) || text( 'error', 'Something went wrong.' ) ); // eslint-disable-line no-alert
			}
		} );
	}

	function handleLoad() {
		var editedElementView = getEditedElementView();
		if ( ! editedElementView ) {
			return;
		}

		var model = editedElementView.model;
		var name = model.getSetting( 'widget_style_preset_select' );

		if ( ! name || ! config().presets[ name ] ) {
			window.alert( text( 'selectFirst', 'Select a preset first.' ) ); // eslint-disable-line no-alert
			return;
		}

		model.setSetting( config().presets[ name ] );

		if ( editedElementView.renderOnChange ) {
			editedElementView.renderOnChange( model );
		}
	}

	function handleRename() {
		var editedElementView = getEditedElementView();
		if ( ! editedElementView ) {
			return;
		}

		var model = editedElementView.model;
		var widgetConfig = getWidgetConfig( editedElementView );
		var oldName = model.getSetting( 'widget_style_preset_select' );
		var newName = ( model.getSetting( 'widget_style_preset_name' ) || '' ).toString().trim();

		if ( ! oldName || ! newName ) {
			window.alert( text( 'selectFirst', 'Select a preset first, and enter the new name.' ) ); // eslint-disable-line no-alert
			return;
		}

		$.post( config().ajaxUrl, {
			action: 'am_rename_widget_style_preset',
			nonce: config().nonce,
			old_name: oldName,
			new_name: newName
		} ).done( function ( response ) {
			if ( response && response.success ) {
				config().presets = response.data.presets;
				refreshSelectOptions( widgetConfig );
				model.setSetting( 'widget_style_preset_select', newName );
				window.alert( text( 'renamed', 'Widget style preset renamed.' ) ); // eslint-disable-line no-alert
			} else {
				window.alert( ( response && response.data && response.data.message ) || text( 'error', 'Something went wrong.' ) ); // eslint-disable-line no-alert
			}
		} );
	}

	function handleDelete() {
		var editedElementView = getEditedElementView();
		if ( ! editedElementView ) {
			return;
		}

		var model = editedElementView.model;
		var widgetConfig = getWidgetConfig( editedElementView );
		var name = model.getSetting( 'widget_style_preset_select' );

		if ( ! name ) {
			window.alert( text( 'selectFirst', 'Select a preset first.' ) ); // eslint-disable-line no-alert
			return;
		}

		if ( ! window.confirm( text( 'confirmDelete', 'Delete this widget style preset? This cannot be undone.' ) ) ) { // eslint-disable-line no-alert
			return;
		}

		$.post( config().ajaxUrl, {
			action: 'am_delete_widget_style_preset',
			nonce: config().nonce,
			name: name
		} ).done( function ( response ) {
			if ( response && response.success ) {
				config().presets = response.data.presets;
				refreshSelectOptions( widgetConfig );
				model.setSetting( 'widget_style_preset_select', '' );
				window.alert( text( 'deleted', 'Widget style preset deleted.' ) ); // eslint-disable-line no-alert
			} else {
				window.alert( ( response && response.data && response.data.message ) || text( 'error', 'Something went wrong.' ) ); // eslint-disable-line no-alert
			}
		} );
	}

	$( window ).on( 'elementor:init', function () {
		elementor.channels.editor.on( 'am:widget_style_save', handleSave );
		elementor.channels.editor.on( 'am:widget_style_load', handleLoad );
		elementor.channels.editor.on( 'am:widget_style_rename', handleRename );
		elementor.channels.editor.on( 'am:widget_style_delete', handleDelete );
	} );
} )( jQuery );
