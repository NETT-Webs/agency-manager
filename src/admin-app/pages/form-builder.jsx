import { useEffect, useMemo, useRef, useState } from 'react';
import { Settings2, Eye, Save, ArrowLeft } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Skeleton } from '../components/ui/skeleton';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { useToast } from '../components/ui/toast';
import { FieldLibrary } from '../components/form-builder/field-library';
import { FormCanvas } from '../components/form-builder/form-canvas';
import { FieldSettings } from '../components/form-builder/field-settings';
import { FormSettingsDialog } from '../components/form-builder/form-settings-dialog';
import { getFormBuilderData, saveFormSchema, formBuilderId, links } from '../lib/api';

function uid() {
	return 'f_' + Math.random().toString( 36 ).slice( 2, 10 ) + Date.now().toString( 36 ).slice( -4 );
}

function slugify( label ) {
	return ( label || '' ).toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' ).slice( 0, 40 ) || 'field';
}

const FIELD_DEFAULTS = {
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
};

function fieldFromLibraryItem( item ) {
	return {
		...FIELD_DEFAULTS,
		id: uid(),
		type: item.type,
		key: slugify( item.key || item.label ),
		label: item.label,
		required: !! item.required,
		options: ( item.options || [] ).slice(),
	};
}

/**
 * The Form Builder as a normal screen inside the Agency Manager app shell —
 * same sidebar/breadcrumbs/design system as every other screen. Loads its
 * data from a thin read-only REST endpoint (Forms_Rest_Controller::
 * get_builder_data(), wraps Form_Renderer::get_fields()/Field_Types::
 * library()/types()/Mapping_Targets::get() exactly as the classic
 * Form_Builder_Page did) and still *saves* through the original
 * Form_Builder_Ajax admin-ajax endpoint, completely unchanged — no form
 * storage, schema, field registry, or save contract was touched to build
 * this screen.
 */
export function FormBuilder() {
	const toast = useToast();
	const [ data, setData ] = useState( null );
	const [ loadError, setLoadError ] = useState( null );
	const [ title, setTitle ] = useState( '' );
	const [ type, setType ] = useState( 'talent' );
	const [ confirmation, setConfirmation ] = useState( '' );
	const [ fields, setFields ] = useState( [] );
	const [ selectedId, setSelectedId ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ settingsOpen, setSettingsOpen ] = useState( false );
	const [ mobileTab, setMobileTab ] = useState( 'canvas' );

	const snapshotRef = useRef( '' );

	function load() {
		setLoadError( null );
		getFormBuilderData( formBuilderId )
			.then( ( res ) => {
				const normalizedFields = res.fields.map( ( f ) => ( { ...FIELD_DEFAULTS, ...f } ) );
				setData( res );
				setTitle( res.title );
				setType( res.type );
				setConfirmation( res.confirmation || '' );
				setFields( normalizedFields );
				setSelectedId( null );
				// Snapshot built from the *same* normalized shape the dirty-check
				// compares against later — comparing against res.fields directly
				// would false-positive on key-order alone (FIELD_DEFAULTS spread
				// reorders keys), marking a freshly loaded, untouched form dirty.
				snapshotRef.current = JSON.stringify( { title: res.title, type: res.type, confirmation: res.confirmation || '', fields: normalizedFields } );
			} )
			.catch( () => setLoadError( 'Could not load this form. It may have been deleted.' ) );
	}

	useEffect( load, [] );

	const dirty = useMemo( () => {
		if ( ! data ) {
			return false;
		}
		return JSON.stringify( { title, type, confirmation, fields } ) !== snapshotRef.current;
	}, [ data, title, type, confirmation, fields ] );

	useEffect( () => {
		function handleBeforeUnload( e ) {
			if ( dirty ) {
				e.preventDefault();
				e.returnValue = '';
			}
		}
		window.addEventListener( 'beforeunload', handleBeforeUnload );
		return () => window.removeEventListener( 'beforeunload', handleBeforeUnload );
	}, [ dirty ] );

	const selectedField = fields.find( ( f ) => f.id === selectedId ) || null;
	const typeMeta = ( data && data.types && selectedField && data.types[ selectedField.type ] ) || { supports: [] };

	function updateField( id, patch ) {
		setFields( ( prev ) => prev.map( ( f ) => ( f.id === id ? { ...f, ...patch } : f ) ) );
	}

	function addFieldAt( item, index ) {
		const field = fieldFromLibraryItem( item );
		setFields( ( prev ) => {
			const next = prev.slice();
			next.splice( index, 0, field );
			return next;
		} );
		setSelectedId( field.id );
		setMobileTab( 'settings' );
	}

	function addField( item ) {
		addFieldAt( item, fields.length );
	}

	function reorderField( id, toIndex ) {
		setFields( ( prev ) => {
			const from = prev.findIndex( ( f ) => f.id === id );
			if ( from === -1 ) {
				return prev;
			}
			const next = prev.slice();
			const [ moved ] = next.splice( from, 1 );
			const adjusted = toIndex > from ? toIndex - 1 : toIndex;
			next.splice( adjusted, 0, moved );
			return next;
		} );
	}

	function moveField( id, direction ) {
		setFields( ( prev ) => {
			const from = prev.findIndex( ( f ) => f.id === id );
			const to = from + direction;
			if ( from === -1 || to < 0 || to >= prev.length ) {
				return prev;
			}
			const next = prev.slice();
			const [ moved ] = next.splice( from, 1 );
			next.splice( to, 0, moved );
			return next;
		} );
	}

	function duplicateField( id ) {
		setFields( ( prev ) => {
			const index = prev.findIndex( ( f ) => f.id === id );
			if ( index === -1 ) {
				return prev;
			}
			const copy = { ...prev[ index ], id: uid(), key: prev[ index ].key + '_copy' };
			const next = prev.slice();
			next.splice( index + 1, 0, copy );
			return next;
		} );
	}

	function deleteField( id ) {
		if ( ! window.confirm( 'Remove this field from the form?' ) ) {
			return;
		}
		setFields( ( prev ) => prev.filter( ( f ) => f.id !== id ) );
		setSelectedId( ( prev ) => ( prev === id ? null : prev ) );
	}

	function selectField( id ) {
		setSelectedId( id );
		setMobileTab( 'settings' );
	}

	function handleSave() {
		setSaving( true );
		saveFormSchema( { formId: formBuilderId, title, formType: type, confirmation, fields } )
			.then( ( result ) => {
				const saved = result && result.fields ? result.fields : fields;
				const normalizedFields = saved.map( ( f ) => ( { ...FIELD_DEFAULTS, ...f } ) );
				setFields( normalizedFields );
				snapshotRef.current = JSON.stringify( { title, type, confirmation, fields: normalizedFields } );
				toast( 'Form saved.' );
			} )
			.catch( ( err ) => toast( err.message || 'Could not save the form.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	if ( loadError ) {
		return (
			<div className="am-flex am-h-full am-flex-col am-items-center am-justify-center am-gap-3 am-p-10 am-text-center">
				<p className="am-text-sm am-text-muted-foreground">{ loadError }</p>
				<Button as="a" href={ links.forms } size="sm" variant="outline"><ArrowLeft className="am-h-4 am-w-4" aria-hidden="true" /> Back to Forms</Button>
			</div>
		);
	}

	if ( ! data ) {
		return (
			<div className="am-flex am-h-full am-flex-col am-gap-4 am-p-6">
				<Skeleton className="am-h-8 am-w-64" />
				<div className="am-grid am-flex-1 am-grid-cols-3 am-gap-4">
					<Skeleton className="am-h-full" />
					<Skeleton className="am-h-full" />
					<Skeleton className="am-h-full" />
				</div>
			</div>
		);
	}

	const panels = (
		<>
			<div className="am-flex am-h-full am-flex-col am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
				<FieldLibrary library={ data.library } onAdd={ addField } />
			</div>
			<div className="am-flex am-h-full am-flex-col am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
				<FormCanvas
					fields={ fields }
					selectedId={ selectedId }
					typesMeta={ data.types }
					onSelect={ selectField }
					onAddAt={ addFieldAt }
					onMove={ moveField }
					onReorder={ reorderField }
					onDuplicate={ duplicateField }
					onDelete={ deleteField }
				/>
			</div>
			<div className="am-flex am-h-full am-flex-col am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
				<FieldSettings
					field={ selectedField }
					allFields={ fields }
					typeMeta={ typeMeta }
					mappingTargets={ data.mappingTargets }
					onChange={ ( patch ) => selectedField && updateField( selectedField.id, patch ) }
				/>
			</div>
		</>
	);

	return (
		<div className="am-flex am-h-full am-flex-col am-gap-4 am-p-6">
			<PageHeader
				breadcrumbs={ [ { label: 'Forms', href: links.forms }, { label: title || 'Untitled Form' } ] }
				title={ title || 'Untitled Form' }
				description={ data.shortcode }
				className="am-mb-0"
				actions={
					<>
						{ dirty ? (
							<Badge variant="warning">Unsaved changes</Badge>
						) : (
							<Badge variant="secondary">All changes saved</Badge>
						) }
						<Button variant="outline" size="sm" onClick={ () => setSettingsOpen( true ) }>
							<Settings2 className="am-h-4 am-w-4" aria-hidden="true" /> Form Settings
						</Button>
						<Button as="a" href={ data.previewUrl } target="_blank" rel="noopener" variant="outline" size="sm">
							<Eye className="am-h-4 am-w-4" aria-hidden="true" /> Preview
						</Button>
						<Button size="sm" disabled={ ! dirty || saving } onClick={ handleSave }>
							<Save className="am-h-4 am-w-4" aria-hidden="true" /> { saving ? 'Saving…' : 'Save' }
						</Button>
					</>
				}
			/>

			{ /* Desktop: fixed three-panel workspace. */ }
			<div className="am-hidden am-min-h-0 am-flex-1 lg:am-grid lg:am-grid-cols-[280px_1fr_340px] lg:am-gap-4">
				{ panels }
			</div>

			{ /* Mobile/tablet: the same three panels, one at a time via tabs. */ }
			<div className="am-flex am-min-h-0 am-flex-1 am-flex-col lg:am-hidden">
				<Tabs value={ mobileTab } onValueChange={ setMobileTab } className="am-flex am-min-h-0 am-flex-1 am-flex-col">
					<TabsList className="am-mb-2 am-w-full">
						<TabsTrigger value="library" className="am-flex-1">Library</TabsTrigger>
						<TabsTrigger value="canvas" className="am-flex-1">Canvas</TabsTrigger>
						<TabsTrigger value="settings" className="am-flex-1">Settings</TabsTrigger>
					</TabsList>
					<TabsContent value="library" className="am-mt-0 am-min-h-0 am-flex-1 am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
						<FieldLibrary library={ data.library } onAdd={ addField } />
					</TabsContent>
					<TabsContent value="canvas" className="am-mt-0 am-min-h-0 am-flex-1 am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
						<FormCanvas
							fields={ fields }
							selectedId={ selectedId }
							typesMeta={ data.types }
							onSelect={ selectField }
							onAddAt={ addFieldAt }
							onMove={ moveField }
							onReorder={ reorderField }
							onDuplicate={ duplicateField }
							onDelete={ deleteField }
						/>
					</TabsContent>
					<TabsContent value="settings" className="am-mt-0 am-min-h-0 am-flex-1 am-overflow-hidden am-rounded-lg am-border am-border-border am-bg-card">
						<FieldSettings
							field={ selectedField }
							allFields={ fields }
							typeMeta={ typeMeta }
							mappingTargets={ data.mappingTargets }
							onChange={ ( patch ) => selectedField && updateField( selectedField.id, patch ) }
						/>
					</TabsContent>
				</Tabs>
			</div>

			<FormSettingsDialog
				open={ settingsOpen }
				onOpenChange={ setSettingsOpen }
				title={ title }
				type={ type }
				confirmation={ confirmation }
				onChange={ ( patch ) => {
					if ( undefined !== patch.title ) setTitle( patch.title );
					if ( undefined !== patch.type ) setType( patch.type );
					if ( undefined !== patch.confirmation ) setConfirmation( patch.confirmation );
				} }
			/>
		</div>
	);
}
