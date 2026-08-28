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
import { talentApi, links, recordId } from '../lib/api';

const AVAILABILITY_OPTIONS = { available: 'Available', limited: 'Limited', booked: 'Booked' };
const BODY_TYPE_OPTIONS = { 'straight-size': 'Straight Size', 'plus-size': 'Plus Size', athletic: 'Athletic', petite: 'Petite', tall: 'Tall' };

const EMPTY_RECORD = {
	title: '', description: '', status: 'draft', thumbnailId: 0, thumbnailUrl: '', galleryIds: [],
	featured: false, homepage: false, active: true,
	terms: { talent_category: [], talent_group: [] },
	meta: { city: '', age: '', availability: '', languages: '', skills: '', experience: '', video_url: '', height: '', body_type: '', hair_color: '', eye_color: '', measurements: '', social_instagram: '', social_facebook: '', social_tiktok: '', social_website: '' },
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

export function TalentEditor() {
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
		talentApi.getOptions().then( setOptions ).catch( () => {} );
		if ( ! isNew ) {
			talentApi.get( recordId ).then( setRecord ).catch( () => toast( 'Could not load this Talent profile.', 'error' ) );
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
		const request = isNew ? talentApi.create( buildBody() ) : talentApi.update( id, buildBody() );

		request
			.then( ( res ) => {
				toast( 'Talent profile saved.' );
				const newId = res.id || id;
				if ( isNew ) {
					setId( newId );
					setIsNew( false );
					window.history.replaceState( {}, '', links.talentEdit + newId );
				}
				return talentApi.get( newId ).then( setRecord );
			} )
			.then( () => {
				if ( andPreview ) {
					setPreviewKey( ( k ) => k + 1 );
					previewRef.current?.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} )
			.catch( () => toast( 'Could not save this Talent profile.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	function handleDelete() {
		setSaving( true );
		talentApi.remove( id )
			.then( () => { toast( 'Talent profile moved to Trash.' ); window.location.href = links.talent; } )
			.catch( () => { toast( 'Could not delete this Talent profile.', 'error' ); setSaving( false ); } );
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
				breadcrumbs={ [ { label: 'Talent', href: links.talent }, { label: isNew ? 'Add Talent' : record.title } ] }
				title={ isNew ? 'Add Talent' : `Edit: ${ record.title }` }
				actions={ <a href={ links.talent } className="am-text-sm am-text-muted-foreground hover:am-text-foreground">&larr; Back to Talent</a> }
			/>

			<div className="am-grid am-grid-cols-1 am-gap-6 lg:am-grid-cols-3">
				<div className="am-flex am-flex-col am-gap-6 lg:am-col-span-2">
					<Card>
						<CardHeader><CardTitle>Basic Information</CardTitle></CardHeader>
						<CardContent className="am-flex am-flex-col am-gap-4">
							<FormField label="Name">
								<Input value={ record.title } onChange={ ( e ) => setField( 'title', e.target.value ) } placeholder="e.g. Amara Okafor" />
							</FormField>
							<FormField label="Profile Photo">
								<FeaturedImagePicker id={ record.thumbnailId } url={ record.thumbnailUrl } onChange={ ( thumbnailId, thumbnailUrl ) => setRecord( ( p ) => ( { ...p, thumbnailId, thumbnailUrl } ) ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Professional Information</CardTitle></CardHeader>
						<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
							<FormField label="Category" className="sm:am-col-span-2">
								<TermChecklist terms={ options.taxonomies.talent_category || [] } selected={ record.terms.talent_category } onChange={ ( ids ) => setTerms( 'talent_category', ids ) } manageUrl={ links.talentCategories } manageLabel="Categories" />
							</FormField>
							<FormField label="Group" className="sm:am-col-span-2">
								<TermChecklist terms={ options.taxonomies.talent_group || [] } selected={ record.terms.talent_group } onChange={ ( ids ) => setTerms( 'talent_group', ids ) } manageUrl={ links.talentGroups } manageLabel="Groups" />
							</FormField>
							<FormField label="City">
								<Input value={ record.meta.city } onChange={ ( e ) => setMeta( 'city', e.target.value ) } />
								<MappedBadge field="city" mappedFields={ record.mappedFields } />
							</FormField>
							<FormField label="Age"><Input value={ record.meta.age } onChange={ ( e ) => setMeta( 'age', e.target.value ) } /></FormField>
							<FormField label="Description" className="sm:am-col-span-2">
								<Textarea rows={ 4 } value={ record.description } onChange={ ( e ) => setField( 'description', e.target.value ) } />
							</FormField>
							<FormField label="Experience" className="sm:am-col-span-2" description="One credit per line.">
								<Textarea rows={ 3 } value={ record.meta.experience } onChange={ ( e ) => setMeta( 'experience', e.target.value ) } />
							</FormField>
							<FormField label="Skills" description="One per line.">
								<Textarea rows={ 3 } value={ record.meta.skills } onChange={ ( e ) => setMeta( 'skills', e.target.value ) } />
							</FormField>
							<FormField label="Languages" description="One per line.">
								<Textarea rows={ 3 } value={ record.meta.languages } onChange={ ( e ) => setMeta( 'languages', e.target.value ) } />
							</FormField>
							<FormField label="Height"><Input value={ record.meta.height } onChange={ ( e ) => setMeta( 'height', e.target.value ) } /></FormField>
							<FormField label="Body Type">
								<MetaSelect value={ record.meta.body_type } onChange={ ( v ) => setMeta( 'body_type', v ) } options={ BODY_TYPE_OPTIONS } />
							</FormField>
							<FormField label="Hair Colour"><Input value={ record.meta.hair_color } onChange={ ( e ) => setMeta( 'hair_color', e.target.value ) } /></FormField>
							<FormField label="Eye Colour"><Input value={ record.meta.eye_color } onChange={ ( e ) => setMeta( 'eye_color', e.target.value ) } /></FormField>
							<FormField label="Measurements" className="sm:am-col-span-2">
								<Textarea rows={ 3 } value={ record.meta.measurements } onChange={ ( e ) => setMeta( 'measurements', e.target.value ) } />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Media</CardTitle></CardHeader>
						<CardContent className="am-flex am-flex-col am-gap-4">
							<FormField label="Gallery" description="The first image is used as this profile's card image throughout the site.">
								<GalleryManager ids={ record.galleryIds } onChange={ ( ids ) => setField( 'galleryIds', ids ) } />
							</FormField>
							<FormField label="Video URL">
								<Input type="url" value={ record.meta.video_url } onChange={ ( e ) => setMeta( 'video_url', e.target.value ) } placeholder="https://" />
							</FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Social Links</CardTitle></CardHeader>
						<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
							<FormField label="Instagram"><Input type="url" value={ record.meta.social_instagram } onChange={ ( e ) => setMeta( 'social_instagram', e.target.value ) } /></FormField>
							<FormField label="Facebook"><Input type="url" value={ record.meta.social_facebook } onChange={ ( e ) => setMeta( 'social_facebook', e.target.value ) } /></FormField>
							<FormField label="TikTok"><Input type="url" value={ record.meta.social_tiktok } onChange={ ( e ) => setMeta( 'social_tiktok', e.target.value ) } /></FormField>
							<FormField label="Website"><Input type="url" value={ record.meta.social_website } onChange={ ( e ) => setMeta( 'social_website', e.target.value ) } /></FormField>
						</CardContent>
					</Card>

					<Card>
						<CardHeader><CardTitle>Availability</CardTitle></CardHeader>
						<CardContent>
							<FormField label="Availability Status">
								<MetaSelect value={ record.meta.availability } onChange={ ( v ) => setMeta( 'availability', v ) } options={ AVAILABILITY_OPTIONS } />
								<MappedBadge field="availability" mappedFields={ record.mappedFields } />
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
					<PublishPanel record={ record } setField={ setField } saving={ saving } isNew={ isNew } entityLabel="Talent"
						onSave={ () => save( false ) } onSaveAndPreview={ () => save( true ) }
						onCancel={ () => { window.location.href = links.talent; } }
						onDelete={ () => setConfirmDelete( true ) } />
					<PreviewPanel recordId={ id } refreshKey={ previewKey } fetcher={ talentApi.preview } />
				</div>
			</div>

			<ConfirmDialog
				open={ confirmDelete }
				onOpenChange={ setConfirmDelete }
				title="Delete this Talent profile?"
				description="It will be moved to Trash and can be restored later if needed."
				destructive
				confirmLabel="Delete"
				loading={ saving }
				onConfirm={ handleDelete }
			/>
		</div>
	);
}
