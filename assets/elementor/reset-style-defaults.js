/**
 * "Reset to Theme Defaults" for Agency Manager's Elementor widgets.
 *
 * Editor-only (enqueued via elementor/editor/after_enqueue_scripts — never
 * loaded on the public-facing site). The BUTTON control registered in
 * Base_Grid_Widget::register_style_controls() (name: reset_style_defaults,
 * event: am:reset_style_defaults) fires this exact event on Elementor's own
 * editor event channel when clicked; nothing else triggers it.
 *
 * Only controls belonging to the Style tab are reset (Elementor's own
 * widget-config cache records each control's 'tab' and 'default' exactly as
 * registered in PHP), and only on the one widget instance currently being
 * edited — Backbone's model.setSetting() writes to that element's own
 * settings model, never anything global, another element, or the theme.
 */
( function ( $ ) {
	'use strict';

	// Controls that manage presets/reset themselves — never "style values"
	// to snapshot or reset, even though they live in Style-tab sections
	// (which is what gives them 'tab' === 'style' in Elementor's config).
	var META_CONTROLS = [
		'reset_style_defaults',
		'widget_style_preset_select',
		'widget_style_preset_name',
		'widget_style_load',
		'widget_style_save',
		'widget_style_rename',
		'widget_style_delete'
	];

	function resetCurrentWidgetStyles() {
		if ( ! window.elementor || ! elementor.channels || ! elementor.getPanelView ) {
			return;
		}

		var panel = elementor.getPanelView();
		var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
		var editedElementView = page && page.getOption && page.getOption( 'editedElementView' );

		if ( ! editedElementView || ! editedElementView.model ) {
			return;
		}

		var model = editedElementView.model;
		var widgetType = model.get( 'widgetType' ) || model.get( 'elType' );
		var widgetsCache = elementor.config && elementor.config.widgets;
		var widgetConfig = widgetsCache && widgetsCache[ widgetType ];

		if ( ! widgetConfig || ! widgetConfig.controls ) {
			return;
		}

		var resetValues = {};

		Object.keys( widgetConfig.controls ).forEach( function ( name ) {
			var control = widgetConfig.controls[ name ];
			if ( control && 'style' === control.tab && META_CONTROLS.indexOf( name ) === -1 ) {
				resetValues[ name ] = ( 'undefined' !== typeof control.default ) ? control.default : '';
			}
		} );

		if ( Object.keys( resetValues ).length === 0 ) {
			return;
		}

		model.setSetting( resetValues );

		if ( editedElementView.renderOnChange ) {
			editedElementView.renderOnChange( model );
		}
	}

	$( window ).on( 'elementor:init', function () {
		elementor.channels.editor.on( 'am:reset_style_defaults', resetCurrentWidgetStyles );
	} );
} )( jQuery );
