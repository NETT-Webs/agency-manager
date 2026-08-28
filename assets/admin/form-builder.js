( function () {
	'use strict';

	var cfg = window.amFormBuilder || {};
	var root = document.getElementById( 'am-form-builder-root' );
	if ( ! root ) {
		return;
	}

	var state = {
		title: cfg.formTitle || '',
		type: cfg.formType || 'talent',
		confirmation: cfg.confirmation || '',
		fields: ( cfg.fields || [] ).map( normalizeClientField ),
		selectedId: null,
		dirty: false,
	};

	var dragOverIndex = null;
	var draggingCanvasId = null;

	function uid() {
		return 'f_' + Math.random().toString( 36 ).slice( 2, 10 ) + Date.now().toString( 36 ).slice( -4 );
	}

	function typeMeta( type ) {
		return ( cfg.types && cfg.types[ type ] ) || { label: type, supports: [] };
	}

	function supports( field, feature ) {
		var meta = typeMeta( field.type );
		return ( meta.supports || [] ).indexOf( feature ) !== -1;
	}

	function normalizeClientField( f ) {
		return Object.assign(
			{
				id: uid(),
				key: '',
				label: '',
				type: 'text',
				required: false,
				description: '',
				placeholder: '',
				default: '',
				css_class: '',
				admin_label: '',
				options: [],
				min_length: null,
				max_length: null,
				file_types: '',
				max_file_size: null,
				max_files: null,
				conditional: null,
				mapping: null,
			},
			f
		);
	}

	function markDirty() {
		state.dirty = true;
		var status = root.querySelector( '.am-fb-status' );
		if ( status ) {
			status.textContent = '';
		}
	}

	function slugify( label ) {
		return ( label || '' )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '_' )
			.replace( /^_+|_+$/g, '' )
			.slice( 0, 40 ) || 'field';
	}

	function createFieldFromLibraryItem( item ) {
		var field = normalizeClientField( {
			type: item.type,
			key: slugify( item.key || item.label ),
			label: item.label,
			required: !! item.required,
			options: ( item.options || [] ).slice(),
		} );
		return field;
	}

	// ---------- Render ----------

	function render() {
		root.innerHTML = '';
		root.appendChild( buildToolbar() );

		var columns = document.createElement( 'div' );
		columns.className = 'am-fb-columns';
		columns.appendChild( buildLibrary() );
		columns.appendChild( buildCanvas() );
		columns.appendChild( buildSettings() );
		root.appendChild( columns );
	}

	function buildToolbar() {
		var bar = document.createElement( 'div' );
		bar.className = 'am-fb-toolbar';

		var title = document.createElement( 'input' );
		title.type = 'text';
		title.className = 'am-fb-title regular-text';
		title.value = state.title;
		title.addEventListener( 'input', function () {
			state.title = title.value;
			markDirty();
		} );
		bar.appendChild( title );

		var typeSelect = document.createElement( 'select' );
		typeSelect.className = 'am-fb-type';
		[ [ 'talent', 'Talent Application' ], [ 'location', 'Location Application' ], [ 'general', 'General / Contact' ] ].forEach( function ( pair ) {
			var opt = document.createElement( 'option' );
			opt.value = pair[ 0 ];
			opt.textContent = pair[ 1 ];
			if ( pair[ 0 ] === state.type ) {
				opt.selected = true;
			}
			typeSelect.appendChild( opt );
		} );
		typeSelect.addEventListener( 'change', function () {
			state.type = typeSelect.value;
			markDirty();
			renderSettingsOnly();
		} );
		bar.appendChild( typeSelect );

		var status = document.createElement( 'span' );
		status.className = 'am-fb-status';
		bar.appendChild( status );

		var saveBtn = document.createElement( 'button' );
		saveBtn.type = 'button';
		saveBtn.className = 'button button-primary am-fb-save';
		saveBtn.textContent = 'Save Form';
		saveBtn.addEventListener( 'click', save );
		bar.appendChild( saveBtn );

		return bar;
	}

	function buildLibrary() {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-library';

		var heading = document.createElement( 'h2' );
		heading.textContent = 'Field Library';
		wrap.appendChild( heading );

		Object.keys( cfg.library || {} ).forEach( function ( groupKey ) {
			var group = cfg.library[ groupKey ];
			var h = document.createElement( 'h3' );
			h.textContent = group.label;
			wrap.appendChild( h );

			var list = document.createElement( 'div' );
			list.className = 'am-fb-lib-items';

			group.items.forEach( function ( item ) {
				var el = document.createElement( 'div' );
				el.className = 'am-fb-lib-item';
				el.setAttribute( 'draggable', 'true' );
				el.innerHTML = '<span class="dashicons ' + ( item.icon || 'dashicons-forms' ) + '"></span><span>' + escapeHtml( item.label ) + '</span>';
				el.addEventListener( 'dragstart', function ( e ) {
					e.dataTransfer.setData( 'text/plain', JSON.stringify( { source: 'library', item: item } ) );
					e.dataTransfer.effectAllowed = 'copy';
				} );
				el.addEventListener( 'click', function () {
					var field = createFieldFromLibraryItem( item );
					state.fields.push( field );
					state.selectedId = field.id;
					markDirty();
					render();
				} );
				list.appendChild( el );
			} );

			wrap.appendChild( list );
		} );

		return wrap;
	}

	function buildCanvas() {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-canvas';
		wrap.id = 'am-fb-canvas';

		var heading = document.createElement( 'h2' );
		heading.textContent = 'Form Canvas';
		wrap.appendChild( heading );

		var list = document.createElement( 'div' );
		list.className = 'am-fb-canvas-list';

		if ( ! state.fields.length ) {
			var empty = document.createElement( 'div' );
			empty.className = 'am-fb-empty';
			empty.textContent = ( cfg.i18n && cfg.i18n.emptyCanvas ) || 'Drag a field from the library to start building your form.';
			list.appendChild( empty );
		}

		state.fields.forEach( function ( field, index ) {
			list.appendChild( buildCanvasRow( field, index ) );
		} );

		list.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			e.dataTransfer.dropEffect = draggingCanvasId ? 'move' : 'copy';
			updateDragIndicator( list, e.clientY );
		} );

		list.addEventListener( 'drop', function ( e ) {
			e.preventDefault();
			clearDragIndicator( list );
			var raw = e.dataTransfer.getData( 'text/plain' );
			if ( ! raw ) {
				return;
			}
			var payload;
			try {
				payload = JSON.parse( raw );
			} catch ( err ) {
				return;
			}

			var index = dragOverIndex === null ? state.fields.length : dragOverIndex;

			if ( 'library' === payload.source ) {
				var field = createFieldFromLibraryItem( payload.item );
				state.fields.splice( index, 0, field );
				state.selectedId = field.id;
			} else if ( 'canvas' === payload.source ) {
				var from = state.fields.findIndex( function ( f ) { return f.id === payload.id; } );
				if ( from === -1 ) {
					return;
				}
				var moved = state.fields.splice( from, 1 )[ 0 ];
				var to = index > from ? index - 1 : index;
				state.fields.splice( to, 0, moved );
			}

			dragOverIndex = null;
			draggingCanvasId = null;
			markDirty();
			render();
		} );

		wrap.appendChild( list );
		return wrap;
	}

	function updateDragIndicator( list, clientY ) {
		var rows = Array.prototype.slice.call( list.querySelectorAll( '.am-fb-row' ) );
		dragOverIndex = rows.length;
		rows.forEach( function ( row, i ) {
			row.classList.remove( 'am-fb-row--drag-before' );
			var rect = row.getBoundingClientRect();
			if ( clientY < rect.top + rect.height / 2 && dragOverIndex === rows.length ) {
				dragOverIndex = i;
			}
		} );
		rows.forEach( function ( row, i ) {
			if ( i === dragOverIndex ) {
				row.classList.add( 'am-fb-row--drag-before' );
			}
		} );
	}

	function clearDragIndicator( list ) {
		list.querySelectorAll( '.am-fb-row--drag-before' ).forEach( function ( row ) {
			row.classList.remove( 'am-fb-row--drag-before' );
		} );
	}

	function buildCanvasRow( field, index ) {
		var row = document.createElement( 'div' );
		row.className = 'am-fb-row' + ( field.id === state.selectedId ? ' am-fb-row--selected' : '' );
		row.setAttribute( 'draggable', 'true' );
		row.setAttribute( 'tabindex', '0' );
		row.setAttribute( 'role', 'button' );
		row.setAttribute( 'aria-label', 'Edit field: ' + ( field.label || field.type ) );

		var handle = document.createElement( 'span' );
		handle.className = 'am-fb-row__handle dashicons dashicons-menu';
		handle.setAttribute( 'aria-hidden', 'true' );
		row.appendChild( handle );

		var main = document.createElement( 'span' );
		main.className = 'am-fb-row__main';
		main.innerHTML = '<strong>' + escapeHtml( field.label || '(untitled)' ) + '</strong>' +
			( field.required ? ' <span class="am-fb-required">*</span>' : '' ) +
			'<br><span class="am-fb-row__meta">' + escapeHtml( typeMeta( field.type ).label || field.type ) + ' &middot; ' + escapeHtml( field.key ) + '</span>';
		row.appendChild( main );

		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'am-fb-row__remove button-link';
		remove.setAttribute( 'aria-label', 'Remove field' );
		remove.innerHTML = '&times;';
		remove.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			if ( ! window.confirm( ( cfg.i18n && cfg.i18n.confirmDeleteField ) || 'Remove this field?' ) ) {
				return;
			}
			state.fields = state.fields.filter( function ( f ) { return f.id !== field.id; } );
			if ( state.selectedId === field.id ) {
				state.selectedId = null;
			}
			markDirty();
			render();
		} );
		row.appendChild( remove );

		row.addEventListener( 'click', function () {
			state.selectedId = field.id;
			render();
		} );
		row.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key || ' ' === e.key ) {
				e.preventDefault();
				state.selectedId = field.id;
				render();
			}
		} );

		row.addEventListener( 'dragstart', function ( e ) {
			draggingCanvasId = field.id;
			e.dataTransfer.setData( 'text/plain', JSON.stringify( { source: 'canvas', id: field.id } ) );
			e.dataTransfer.effectAllowed = 'move';
		} );
		row.addEventListener( 'dragend', function () {
			draggingCanvasId = null;
		} );

		return row;
	}

	function renderSettingsOnly() {
		var existing = root.querySelector( '.am-fb-settings' );
		if ( existing ) {
			existing.replaceWith( buildSettings() );
		}
	}

	function buildSettings() {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-settings';

		var heading = document.createElement( 'h2' );
		heading.textContent = 'Field Settings';
		wrap.appendChild( heading );

		var field = state.fields.find( function ( f ) { return f.id === state.selectedId; } );

		if ( ! field ) {
			var empty = document.createElement( 'p' );
			empty.className = 'am-fb-empty';
			empty.textContent = ( cfg.i18n && cfg.i18n.noFieldSelected ) || 'Select a field to edit its settings.';
			wrap.appendChild( empty );
			return wrap;
		}

		wrap.appendChild( buildFieldSettingsForm( field ) );
		return wrap;
	}

	function row( labelText, inputEl ) {
		var r = document.createElement( 'div' );
		r.className = 'am-fb-field-row';
		var label = document.createElement( 'label' );
		label.textContent = labelText;
		r.appendChild( label );
		r.appendChild( inputEl );
		return r;
	}

	function textInput( value, onInput, placeholder ) {
		var input = document.createElement( 'input' );
		input.type = 'text';
		input.className = 'regular-text';
		input.value = value || '';
		if ( placeholder ) {
			input.placeholder = placeholder;
		}
		input.addEventListener( 'input', function () {
			onInput( input.value );
			markDirty();
		} );
		return input;
	}

	function numberInput( value, onInput ) {
		var input = document.createElement( 'input' );
		input.type = 'number';
		input.className = 'small-text';
		input.value = null === value || undefined === value ? '' : value;
		input.addEventListener( 'input', function () {
			onInput( '' === input.value ? null : parseInt( input.value, 10 ) );
			markDirty();
		} );
		return input;
	}

	function checkboxInput( checked, onChange, labelText ) {
		var wrap = document.createElement( 'label' );
		wrap.className = 'am-fb-checkbox';
		var input = document.createElement( 'input' );
		input.type = 'checkbox';
		input.checked = !! checked;
		input.addEventListener( 'change', function () {
			onChange( input.checked );
			markDirty();
		} );
		wrap.appendChild( input );
		wrap.appendChild( document.createTextNode( ' ' + labelText ) );
		return wrap;
	}

	function selectInput( value, options, onChange ) {
		var select = document.createElement( 'select' );
		options.forEach( function ( opt ) {
			var o = document.createElement( 'option' );
			o.value = opt[ 0 ];
			o.textContent = opt[ 1 ];
			if ( opt[ 0 ] === value ) {
				o.selected = true;
			}
			select.appendChild( o );
		} );
		select.addEventListener( 'change', function () {
			onChange( select.value );
			markDirty();
		} );
		return select;
	}

	function buildFieldSettingsForm( field ) {
		var form = document.createElement( 'div' );

		form.appendChild( row( 'Label', textInput( field.label, function ( v ) { field.label = v; renderCanvasRowLabelUpdate(); } ) ) );

		var keyInput = textInput( field.key, function ( v ) { field.key = slugify( v ); } );
		form.appendChild( row( 'Field Name / Key', keyInput ) );

		form.appendChild( row( 'Description', textInput( field.description, function ( v ) { field.description = v; } ) ) );

		if ( supports( field, 'placeholder' ) ) {
			form.appendChild( row( 'Placeholder', textInput( field.placeholder, function ( v ) { field.placeholder = v; } ) ) );
		}

		form.appendChild( row( 'Required', checkboxInput( field.required, function ( v ) { field.required = v; renderCanvasRowLabelUpdate(); }, 'This field is required' ) ) );

		if ( supports( field, 'default' ) ) {
			form.appendChild( row( 'Default Value', textInput( field.default, function ( v ) { field.default = v; } ) ) );
		}

		form.appendChild( row( 'CSS Class', textInput( field.css_class, function ( v ) { field.css_class = v; } ) ) );
		form.appendChild( row( 'Admin-Only Description', textInput( field.admin_label, function ( v ) { field.admin_label = v; }, 'Internal note, never shown publicly' ) ) );

		if ( supports( field, 'options' ) ) {
			form.appendChild( buildOptionsEditor( field ) );
		}

		if ( supports( field, 'file' ) ) {
			form.appendChild( row( 'Allowed File Types', textInput( field.file_types, function ( v ) { field.file_types = v; }, 'e.g. jpg,png,pdf' ) ) );
			form.appendChild( row( 'Max File Size (MB)', numberInput( field.max_file_size, function ( v ) { field.max_file_size = v; } ) ) );
			if ( 'files' === field.type || 'gallery' === field.type ) {
				form.appendChild( row( 'Max Number of Files', numberInput( field.max_files, function ( v ) { field.max_files = v; } ) ) );
			}
		}

		if ( supports( field, 'length' ) ) {
			form.appendChild( row( 'Minimum Length', numberInput( field.min_length, function ( v ) { field.min_length = v; } ) ) );
			form.appendChild( row( 'Maximum Length', numberInput( field.max_length, function ( v ) { field.max_length = v; } ) ) );
		}

		form.appendChild( buildMappingEditor( field ) );
		form.appendChild( buildConditionalEditor( field ) );

		return form;
	}

	function renderCanvasRowLabelUpdate() {
		var canvas = root.querySelector( '.am-fb-canvas' );
		if ( canvas ) {
			canvas.replaceWith( buildCanvas() );
		}
	}

	function buildOptionsEditor( field ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-options-editor';
		var h = document.createElement( 'h4' );
		h.textContent = 'Options';
		wrap.appendChild( h );

		var list = document.createElement( 'div' );

		function redrawOptions() {
			list.innerHTML = '';
			( field.options || [] ).forEach( function ( opt, i ) {
				var optRow = document.createElement( 'div' );
				optRow.className = 'am-fb-option-row';

				var labelInput = document.createElement( 'input' );
				labelInput.type = 'text';
				labelInput.value = opt.label || '';
				labelInput.placeholder = 'Label';
				labelInput.addEventListener( 'input', function () {
					opt.label = labelInput.value;
					if ( ! opt.value ) {
						opt.value = slugify( labelInput.value );
						valueInput.value = opt.value;
					}
					markDirty();
				} );

				var valueInput = document.createElement( 'input' );
				valueInput.type = 'text';
				valueInput.value = opt.value || '';
				valueInput.placeholder = 'Value';
				valueInput.addEventListener( 'input', function () {
					opt.value = valueInput.value;
					markDirty();
				} );

				var removeBtn = document.createElement( 'button' );
				removeBtn.type = 'button';
				removeBtn.className = 'button-link';
				removeBtn.innerHTML = '&times;';
				removeBtn.addEventListener( 'click', function () {
					field.options.splice( i, 1 );
					markDirty();
					redrawOptions();
				} );

				optRow.appendChild( labelInput );
				optRow.appendChild( valueInput );
				optRow.appendChild( removeBtn );
				list.appendChild( optRow );
			} );
		}

		redrawOptions();
		wrap.appendChild( list );

		var addBtn = document.createElement( 'button' );
		addBtn.type = 'button';
		addBtn.className = 'button button-small';
		addBtn.textContent = '+ Add Option';
		addBtn.addEventListener( 'click', function () {
			field.options = field.options || [];
			field.options.push( { label: '', value: '' } );
			markDirty();
			redrawOptions();
		} );
		wrap.appendChild( addBtn );

		return wrap;
	}

	function buildMappingEditor( field ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-mapping-editor';
		var h = document.createElement( 'h4' );
		h.textContent = 'Save Submission Field To';
		wrap.appendChild( h );

		var mapping = field.mapping || { destination: 'none', target: 'existing', target_key: '', target_kind: 'meta' };
		field.mapping = mapping;

		var destSelect = selectInput(
			mapping.destination || 'none',
			[ [ 'none', 'Application Only' ], [ 'talent', 'Talent' ], [ 'location', 'Location' ] ],
			function ( v ) {
				mapping.destination = v;
				mapping.target = 'existing';
				mapping.target_key = '';
				redrawTargetArea();
			}
		);
		wrap.appendChild( row( 'Destination', destSelect ) );

		var targetArea = document.createElement( 'div' );
		wrap.appendChild( targetArea );

		function redrawTargetArea() {
			targetArea.innerHTML = '';

			if ( 'none' === mapping.destination ) {
				return;
			}

			var targets = ( cfg.mappingTargets && cfg.mappingTargets[ mapping.destination ] ) || [];

			var modeSelect = selectInput(
				mapping.target || 'existing',
				[ [ 'existing', 'Existing Field' ], [ 'custom', 'Create New Custom Field' ] ],
				function ( v ) {
					mapping.target = v;
					mapping.target_key = '';
					mapping.target_kind = 'custom' === v ? 'meta' : mapping.target_kind;
					redrawTargetArea();
				}
			);
			targetArea.appendChild( row( 'Target', modeSelect ) );

			if ( 'custom' === mapping.target ) {
				mapping.target_kind = 'meta';
				var customKey = textInput( mapping.target_key, function ( v ) { mapping.target_key = slugify( v ); }, 'e.g. years_of_experience' );
				targetArea.appendChild( row( 'New Custom Field Key', customKey ) );
			} else {
				var options = targets.map( function ( t ) { return [ t.key, t.label ]; } );
				if ( ! options.length ) {
					options.push( [ '', '—' ] );
				}
				var current = targets.find( function ( t ) { return t.key === mapping.target_key; } );
				if ( ! current && options.length ) {
					mapping.target_key = options[ 0 ][ 0 ];
					mapping.target_kind = ( targets[ 0 ] && targets[ 0 ].kind ) || 'meta';
				}
				var existingSelect = selectInput( mapping.target_key, options, function ( v ) {
					mapping.target_key = v;
					var match = targets.find( function ( t ) { return t.key === v; } );
					mapping.target_kind = match ? match.kind : 'meta';
				} );
				targetArea.appendChild( row( 'Existing Field', existingSelect ) );
			}
		}

		redrawTargetArea();

		return wrap;
	}

	function buildConditionalEditor( field ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'am-fb-conditional-editor';
		var h = document.createElement( 'h4' );
		h.textContent = 'Conditional Logic';
		wrap.appendChild( h );

		var enabled = !! field.conditional;
		var enableCheckbox = checkboxInput( enabled, function ( v ) {
			field.conditional = v ? { field_id: '', operator: 'is', value: '' } : null;
			redraw();
		}, 'Only show this field conditionally' );
		wrap.appendChild( enableCheckbox );

		var area = document.createElement( 'div' );
		wrap.appendChild( area );

		function redraw() {
			area.innerHTML = '';
			if ( ! field.conditional ) {
				return;
			}

			var others = state.fields.filter( function ( f ) { return f.id !== field.id; } );
			var fieldOptions = others.map( function ( f ) { return [ f.id, f.label || f.key ]; } );
			if ( ! fieldOptions.length ) {
				var note = document.createElement( 'p' );
				note.className = 'description';
				note.textContent = 'Add another field first to reference it here.';
				area.appendChild( note );
				return;
			}

			if ( ! field.conditional.field_id ) {
				field.conditional.field_id = fieldOptions[ 0 ][ 0 ];
			}

			area.appendChild( row( 'Show this field if', selectInput( field.conditional.field_id, fieldOptions, function ( v ) {
				field.conditional.field_id = v;
			} ) ) );

			area.appendChild( row( 'Condition', selectInput( field.conditional.operator, [ [ 'is', 'Is' ], [ 'is_not', 'Is Not' ] ], function ( v ) {
				field.conditional.operator = v;
			} ) ) );

			var sourceField = state.fields.find( function ( f ) { return f.id === field.conditional.field_id; } );
			var valueControl;
			if ( sourceField && sourceField.options && sourceField.options.length ) {
				valueControl = selectInput( field.conditional.value, sourceField.options.map( function ( o ) { return [ o.value, o.label ]; } ), function ( v ) {
					field.conditional.value = v;
				} );
			} else {
				valueControl = textInput( field.conditional.value, function ( v ) { field.conditional.value = v; } );
			}
			area.appendChild( row( 'Value', valueControl ) );
		}

		redraw();

		return wrap;
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str || '';
		return div.innerHTML;
	}

	// ---------- Save ----------

	function save() {
		var status = root.querySelector( '.am-fb-status' );
		if ( status ) {
			status.textContent = 'Saving…';
		}

		var body = new URLSearchParams();
		body.set( 'action', 'am_save_form_schema' );
		body.set( 'nonce', cfg.nonce );
		body.set( 'form_id', cfg.formId );
		body.set( 'title', state.title );
		body.set( 'form_type', state.type );
		body.set( 'confirmation_message', state.confirmation );
		body.set( 'fields', JSON.stringify( state.fields ) );

		fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( json ) {
				if ( json && json.success ) {
					state.dirty = false;
					if ( status ) {
						status.textContent = ( cfg.i18n && cfg.i18n.saved ) || 'Saved.';
					}
				} else {
					if ( status ) {
						status.textContent = ( json && json.data && json.data.message ) || ( cfg.i18n && cfg.i18n.error ) || 'Error saving.';
					}
				}
			} )
			.catch( function () {
				if ( status ) {
					status.textContent = ( cfg.i18n && cfg.i18n.error ) || 'Error saving.';
				}
			} );
	}

	window.addEventListener( 'beforeunload', function ( e ) {
		if ( state.dirty ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );

	var copyBtn = document.getElementById( 'am-fb-copy-shortcode' );
	if ( copyBtn ) {
		copyBtn.addEventListener( 'click', function () {
			var text = copyBtn.getAttribute( 'data-shortcode' ) || '';
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text );
			}
			copyBtn.textContent = ( cfg.i18n && cfg.i18n.copied ) || 'Copied.';
			setTimeout( function () {
				copyBtn.textContent = 'Copy';
			}, 1500 );
		} );
	}

	render();
} )();
