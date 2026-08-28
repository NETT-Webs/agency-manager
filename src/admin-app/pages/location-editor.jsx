import { useEffect, useRef, useState } from 'react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Skeleton } from '../components/ui/skeleton';
import { Input, Textarea, FormField } from '../components/ui/form-controls';
import { ConfirmDialog } from '../components/ui/confirm-dialog';
import { useToast } from '../components/ui/toast';
import { FeaturedImagePicker } from '../components/profile-editor/featured-image-picker';
import { GalleryManager } from '../components/profile-editor/gallery-manager';
import { MetaSelect } from '../components/profile-editor/meta-select';
import { TermChecklist } from '../components/profile-editor/term-checklist';
import { PreviewPanel } from '../components/profile-editor/preview-panel';
import { PublishPanel } from '../components/profile-editor/publish-panel';
import { locationApi, links, recordId } from '../lib/api';

const PARKING_OPTIONS = { available: 'Available', limited: 'Limited', none: 'Not Available' };
const POWER_OPTIONS = { mains: 'Mains Power', generator: 'Generator Required', limited: 'Limited' };
const AVAILABILITY_OPTIONS = { available: 'Available', booked: 'Booked', seasonal: 'Seasonal' };

const EMPTY_RECORD = {
	title: '', description: '', status: 'draft', thumbnailId: 0, thumbnailUrl: '', galleryIds: [],
	featured: false, homepage: false, active: true,
	terms: { location_type: [] },
	meta: { city: '', parking: '', power: '', amenities: '', availability: '', map_embed: '' },
	customFields: {}, mappedFields: {}, viewUrl: '', editedAt: '',
};

function MappedBadge( { field, mappedFields } ) {
	const sources = mappedFields?.[ field ];
	if ( ! sources || sources.length === 0 ) {
		return null;
	}
	return (
		<p className="am-mt-1 am-text-xs am-text-muted-foreground">
			Filled from application forms: { sources.map( ( s ) => `${ s.formTitle } → ${ s.fieldLabel }` ).join( ', ' ) }
		</p>
	);
}

export function LocationEditor() {
	const toast = useToast();
	const [ isNew, setIsNew ] = useState( ! recordId );
	const [ id, setId ] = useState( recordId );
	const [ record, setRecord ] = useState( isNew ? EMPTY_RECORD : null );
	const [ options, setOptions ] = useState( { taxonomies: {}, customFields: [] } );
	const [ saving, setSaving ] = useState( false );
	const [ confirmDelete, setConfirmDelete ] = useState( false );
	const [ previewKey, setPreviewKey ] = useState( 0 );
	const previewRef = useRef( null );

	useEffect( () => {
		locationApi.getOptions().then( setOptions ).catch( () => {} );
		if ( ! isNew ) {
			locationApi.get( recordId ).then( setRecord ).catch( () => toast( 'Could not load this Location.', 'error' ) );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	function setField( key, value ) {
		setRecord( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}

	function setMeta( key, value ) {
		setRecord( ( prev ) => ( { ...prev, meta: { ...prev.meta, [ key ]: value } } ) );
	}

	function setCustom( key, value ) {
		setRecord( ( prev ) => ( { ...prev, customFields: { ...prev.customFields, [ key ]: value } } ) );
	}

	function setTerms( taxonomy, ids ) {
		setRecord( ( prev ) => ( { ...prev, terms: { ...prev.terms, [ taxonomy ]: ids } } ) );
	}

	function buildBody() {
		return {
			title: record.title, description: record.description, status: record.status,
			thumbnailId: record.thumbnailId, galleryIds: record.galleryIds,
			featured: record.featured, active: record.active, homepage: record.homepage,
			terms: record.terms, meta: record.meta, customFields: record.customFields,
		};
	}

	function save( andPreview ) {
		if ( ! record.title.trim() ) {
			toast( 'Please enter a name before saving.', 'error' );
			return;
		}
		setSaving( true );
		const request = isNew ? locationApi.create( buildBody() ) : locationApi.update( id, buildBody() );

		request
			.then( ( res ) => {
				toast( 'Location saved.' );
				const newId = res.id || id;
				if ( isNew ) {
					setId( newId );
					setIsNew( false );
					window.history.replaceState( {}, '', links.locationEdit + newId );
				}
				return locationApi.get( newId ).then( setRecord );
			} )
			.then( () => {
				if ( andPreview ) {
					setPreviewKey( ( k ) => k + 1 );
					previewRef.current?.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} )
			.catch( () => toast( 'Could not save this Location.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	function handleDelete() {
		setSaving( true );
		locationApi.remove( id )
			.then( () => { toast( 'Location moved to Trash.' ); window.location.href = links.locations; } )
			.catch( () => { toast( 'Could not delete this Location.', 'error' ); setSaving( false ); } );
	}

	if ( ! record ) {
		return (
			<div className="am-flex am-flex-col am-gap-4">
				<Skeleton className="am-h-8 am-w-64" />
				<Skeleton className="am-h-96 am-w-full" />
			</div>
		);
	}

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				breadcrumbs={ [ { label: 'Locations', href: links.locations }, { label: isNew ? 'Add Location' : record.title } ] }
				title={ isNew ? 'Add Location' : `Edit: ${ record.title }` }
				actions={ <a href={ links.locations } className="am-text-sm am-text-muted-foreground hover:am-text-foreground">&larr; Back to Locations</a> }
			/>

			<div className="am-grid am-grid-cols-1 am-gap-6 lg:am-grid-cols-3">
				<div className="am-flex am-flex-col am-gap-6 lg:am-col-span-2">
					<Card>
						<CardHeader><CardTitle>Basic Information</CardTitle></CardHeader>
						<CardContent className="am-flex am-flex-col am-gap-4">
							<FormField label="Location Name">
								<Input value={ record.title } onChange={ ( e ) => setField( 'title', e.target.value ) } placeholder="e.g. Atlantic Light Studio" />
							</FormField>
							<FormField label="Featured Image">
								<FeaturedImagePicker id={ record.thumbnailId } url={ record.thumbnailUrl } onChange={ ( thumbnailId, thumbnailUrl ) => setRecord( ( p ) => ( { ...p, thumbnailId, thumbnailUrl } ) ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Location Details</CardTitle></CardHeader>
						<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
							<FormField label="Type" className="sm:am-col-span-2">
								<TermChecklist terms={ options.taxonomies.location_type || [] } selected={ record.terms.location_type } onChange={ ( ids ) => setTerms( 'location_type', ids ) } manageUrl={ links.locationCategories } manageLabel="Categories" />
							</FormField>
							<FormField label="City / Area">
								<Input value={ record.meta.city } onChange={ ( e ) => setMeta( 'city', e.target.value ) } />
								<MappedBadge field="city" mappedFields={ record.mappedFields } />
							</FormField>
							<FormField label="Availability">
								<MetaSelect value={ record.meta.availability } onChange={ ( v ) => setMeta( 'availability', v ) } options={ AVAILABILITY_OPTIONS } />
							</FormField>
							<FormField label="Description" className="sm:am-col-span-2">
								<Textarea rows={ 4 } value={ record.description } onChange={ ( e ) => setField( 'description', e.target.value ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Media</CardTitle></CardHeader>
						<CardContent>
							<FormField label="Gallery" description="The first image is used as this location's card image throughout the site.">
								<GalleryManager ids={ record.galleryIds } onChange={ ( ids ) => setField( 'galleryIds', ids ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Facilities</CardTitle></CardHeader>
						<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
							<FormField label="Parking">
								<MetaSelect value={ record.meta.parking } onChange={ ( v ) => setMeta( 'parking', v ) } options={ PARKING_OPTIONS } />
							</FormField>
							<FormField label="Power">
								<MetaSelect value={ record.meta.power } onChange={ ( v ) => setMeta( 'power', v ) } options={ POWER_OPTIONS } />
							</FormField>
							<FormField label="Amenities" className="sm:am-col-span-2" description="One per line.">
								<Textarea rows={ 3 } value={ record.meta.amenities } onChange={ ( e ) => setMeta( 'amenities', e.target.value ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Map</CardTitle></CardHeader>
						<CardContent>
							<FormField label="Google Maps Embed URL">
								<Input type="url" value={ record.meta.map_embed } onChange={ ( e ) => setMeta( 'map_embed', e.target.value ) } placeholder="https://" />
							</FormField>
						</CardContent>
					</Card>

					{ options.customFields.length > 0 && (
						<Card>
							<CardHeader><CardTitle>Additional Fields</CardTitle></CardHeader>
							<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
								{ options.customFields.map( ( f ) => (
									<FormField key={ f.key } label={ f.label }>
										<Input value={ record.customFields[ f.key ] || '' } onChange={ ( e ) => setCustom( f.key, e.target.value ) } />
										<MappedBadge field={ f.key } mappedFields={ record.mappedFields } />
									</FormField>
								) ) }
							</CardContent>
						</Card>
					) }
				</div>

				<div ref={ previewRef } className="am-flex am-flex-col am-gap-6">
					<PublishPanel record={ record } setField={ setField } saving={ saving } isNew={ isNew } entityLabel="Location"
						onSave={ () => save( false ) } onSaveAndPreview={ () => save( true ) }
						onCancel={ () => { window.location.href = links.locations; } }
						onDelete={ () => setConfirmDelete( true ) } />
					<PreviewPanel recordId={ id } refreshKey={ previewKey } fetcher={ locationApi.preview } />
				</div>
			</div>

			<ConfirmDialog
				open={ confirmDelete }
				onOpenChange={ setConfirmDelete }
				title="Delete this Location?"
				description="It will be moved to Trash and can be restored later if needed."
				destructive
				confirmLabel="Delete"
				loading={ saving }
				onConfirm={ handleDelete }
			/>
		</div>
	);
}
