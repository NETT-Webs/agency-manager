import { Plus, X, MousePointerClick } from 'lucide-react';
import { Input, Checkbox, FormField } from '../ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../ui/select';
import { Button } from '../ui/button';
import { EmptyState } from '../ui/empty-state';

function slugify( label ) {
	return ( label || '' ).toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' ).slice( 0, 40 ) || 'field';
}

function Section( { title, children } ) {
	return (
		<div className="am-border-b am-border-border am-p-4 last:am-border-b-0">
			<h4 className="am-mb-3 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">{ title }</h4>
			<div className="am-flex am-flex-col am-gap-3">{ children }</div>
		</div>
	);
}

/**
 * Right panel — every setting a stored field definition supports
 * (Form_Schema::normalize_field()'s full shape), organized into sections and
 * gated by Field_Types::types()[type].supports so a Heading field never
 * shows a "Placeholder" box etc. Fully controlled: every change calls
 * `onChange(patch)` which the page merges into its field list — this
 * component holds no state of its own.
 */
export function FieldSettings( { field, allFields, typeMeta, mappingTargets, onChange } ) {
	if ( ! field ) {
		return (
			<div className="am-p-3">
				<EmptyState icon={ MousePointerClick } title="No field selected" description="Select a field on the canvas to edit its settings." />
			</div>
		);
	}

	const supports = ( feature ) => ( typeMeta.supports || [] ).includes( feature );

	return (
		<div className="am-h-full am-overflow-y-auto">
			<Section title="Basic">
				<FormField label="Label" htmlFor="am-fs-label">
					<Input id="am-fs-label" value={ field.label } onChange={ ( e ) => onChange( { label: e.target.value } ) } />
				</FormField>
				<FormField label="Field Name / Key" htmlFor="am-fs-key" description="Used internally to store submitted values — changing it does not affect already-submitted data.">
					<Input id="am-fs-key" value={ field.key } onChange={ ( e ) => onChange( { key: slugify( e.target.value ) } ) } />
				</FormField>
				<FormField label="Description" htmlFor="am-fs-desc">
					<Input id="am-fs-desc" value={ field.description } onChange={ ( e ) => onChange( { description: e.target.value } ) } />
				</FormField>
				<label className="am-flex am-items-center am-gap-2 am-text-sm am-text-foreground">
					<Checkbox checked={ field.required } onChange={ ( e ) => onChange( { required: e.target.checked } ) } />
					This field is required
				</label>
			</Section>

			{ ( supports( 'placeholder' ) || supports( 'default' ) ) && (
				<Section title="Display">
					{ supports( 'placeholder' ) && (
						<FormField label="Placeholder" htmlFor="am-fs-placeholder">
							<Input id="am-fs-placeholder" value={ field.placeholder } onChange={ ( e ) => onChange( { placeholder: e.target.value } ) } />
						</FormField>
					) }
					{ supports( 'default' ) && (
						<FormField label="Default Value" htmlFor="am-fs-default">
							<Input id="am-fs-default" value={ field.default } onChange={ ( e ) => onChange( { default: e.target.value } ) } />
						</FormField>
					) }
					<FormField label="CSS Class" htmlFor="am-fs-css">
						<Input id="am-fs-css" value={ field.css_class } onChange={ ( e ) => onChange( { css_class: e.target.value } ) } />
					</FormField>
					<FormField label="Admin-Only Note" htmlFor="am-fs-admin-label" description="Internal note, never shown publicly.">
						<Input id="am-fs-admin-label" value={ field.admin_label } onChange={ ( e ) => onChange( { admin_label: e.target.value } ) } />
					</FormField>
				</Section>
			) }

			{ supports( 'options' ) && (
				<Section title="Options">
					<OptionsEditor field={ field } onChange={ onChange } />
				</Section>
			) }

			{ ( supports( 'file' ) || supports( 'length' ) ) && (
				<Section title="Validation">
					{ supports( 'file' ) && (
						<>
							<FormField label="Allowed File Types" htmlFor="am-fs-filetypes" description="e.g. jpg,png,pdf">
								<Input id="am-fs-filetypes" value={ field.file_types } onChange={ ( e ) => onChange( { file_types: e.target.value } ) } />
							</FormField>
							<FormField label="Max File Size (MB)" htmlFor="am-fs-maxsize">
								<Input id="am-fs-maxsize" type="number" value={ field.max_file_size ?? '' } onChange={ ( e ) => onChange( { max_file_size: '' === e.target.value ? null : parseInt( e.target.value, 10 ) } ) } />
							</FormField>
							{ ( 'files' === field.type || 'gallery' === field.type ) && (
								<FormField label="Max Number of Files" htmlFor="am-fs-maxfiles">
									<Input id="am-fs-maxfiles" type="number" value={ field.max_files ?? '' } onChange={ ( e ) => onChange( { max_files: '' === e.target.value ? null : parseInt( e.target.value, 10 ) } ) } />
								</FormField>
							) }
						</>
					) }
					{ supports( 'length' ) && (
						<div className="am-grid am-grid-cols-2 am-gap-3">
							<FormField label="Min Length" htmlFor="am-fs-minlen">
								<Input id="am-fs-minlen" type="number" value={ field.min_length ?? '' } onChange={ ( e ) => onChange( { min_length: '' === e.target.value ? null : parseInt( e.target.value, 10 ) } ) } />
							</FormField>
							<FormField label="Max Length" htmlFor="am-fs-maxlen">
								<Input id="am-fs-maxlen" type="number" value={ field.max_length ?? '' } onChange={ ( e ) => onChange( { max_length: '' === e.target.value ? null : parseInt( e.target.value, 10 ) } ) } />
							</FormField>
						</div>
					) }
				</Section>
			) }

			<Section title="Conditional Logic">
				<ConditionalEditor field={ field } allFields={ allFields } onChange={ onChange } />
			</Section>

			<Section title="Advanced — Save Submission Field To">
				<MappingEditor field={ field } mappingTargets={ mappingTargets } onChange={ onChange } />
			</Section>
		</div>
	);
}

function OptionsEditor( { field, onChange } ) {
	const options = field.options || [];

	function updateOption( i, patch ) {
		const next = options.map( ( opt, idx ) => ( idx === i ? { ...opt, ...patch } : opt ) );
		onChange( { options: next } );
	}

	function removeOption( i ) {
		onChange( { options: options.filter( ( _, idx ) => idx !== i ) } );
	}

	return (
		<div className="am-flex am-flex-col am-gap-2">
			{ options.map( ( opt, i ) => (
				<div key={ i } className="am-flex am-items-center am-gap-1.5">
					<Input
						value={ opt.label }
						placeholder="Label"
						className="am-h-8"
						onChange={ ( e ) => {
							const label = e.target.value;
							updateOption( i, opt.value ? { label } : { label, value: slugify( label ) } );
						} }
					/>
					<Input value={ opt.value } placeholder="Value" className="am-h-8" onChange={ ( e ) => updateOption( i, { value: e.target.value } ) } />
					<button type="button" aria-label="Remove option" onClick={ () => removeOption( i ) } className="am-flex am-h-8 am-w-8 am-shrink-0 am-items-center am-justify-center am-rounded am-text-muted-foreground hover:am-bg-destructive/10 hover:am-text-destructive">
						<X className="am-h-3.5 am-w-3.5" aria-hidden="true" />
					</button>
				</div>
			) ) }
			<Button type="button" variant="outline" size="sm" onClick={ () => onChange( { options: [ ...options, { label: '', value: '' } ] } ) }>
				<Plus className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Add Option
			</Button>
		</div>
	);
}

function MappingEditor( { field, mappingTargets, onChange } ) {
	const mapping = field.mapping || { destination: 'none', target: 'existing', target_key: '', target_kind: 'meta' };

	function setMapping( patch ) {
		onChange( { mapping: { ...mapping, ...patch } } );
	}

	// Legacy data can carry destination "both" (Field_Mapper::apply_mapping()
	// writes it to whichever post type the submission becomes — see
	// class-field-mapper.php); it predates the visual builder and was never
	// selectable from its dropdown either, so it's kept here as a real,
	// selectable option instead of silently downgrading it to "talent" the
	// moment an untouched field is re-saved.
	const targetGroup = 'both' === mapping.destination ? 'talent' : mapping.destination;
	const targets = mappingTargets[ targetGroup ] || [];
	const current = targets.find( ( t ) => t.key === mapping.target_key );

	return (
		<div className="am-flex am-flex-col am-gap-3">
			<FormField label="Destination">
				<Select value={ mapping.destination || 'none' } onValueChange={ ( v ) => setMapping( { destination: v, target: 'existing', target_key: '' } ) }>
					<SelectTrigger><SelectValue /></SelectTrigger>
					<SelectContent>
						<SelectItem value="none">Application Only</SelectItem>
						<SelectItem value="talent">Talent</SelectItem>
						<SelectItem value="location">Location</SelectItem>
						<SelectItem value="both">Talent &amp; Location</SelectItem>
					</SelectContent>
				</Select>
			</FormField>

			{ 'none' !== mapping.destination && (
				<>
					<FormField label="Target">
						<Select value={ mapping.target || 'existing' } onValueChange={ ( v ) => setMapping( { target: v, target_key: '', target_kind: 'custom' === v ? 'meta' : mapping.target_kind } ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="existing">Existing Field</SelectItem>
								<SelectItem value="custom">Create New Custom Field</SelectItem>
							</SelectContent>
						</Select>
					</FormField>

					{ 'custom' === mapping.target ? (
						<FormField label="New Custom Field Key">
							<Input value={ mapping.target_key } placeholder="e.g. years_of_experience" onChange={ ( e ) => setMapping( { target_key: slugify( e.target.value ), target_kind: 'meta' } ) } />
						</FormField>
					) : targets.length === 0 ? (
						<p className="am-text-xs am-text-muted-foreground">No existing fields available for this destination.</p>
					) : (
						<FormField label="Existing Field">
							<Select
								value={ current ? mapping.target_key : targets[ 0 ].key }
								onValueChange={ ( v ) => {
									const match = targets.find( ( t ) => t.key === v );
									setMapping( { target_key: v, target_kind: match ? match.kind : 'meta' } );
								} }
							>
								<SelectTrigger><SelectValue /></SelectTrigger>
								<SelectContent>
									{ targets.map( ( t ) => <SelectItem key={ t.key } value={ t.key }>{ t.label }</SelectItem> ) }
								</SelectContent>
							</Select>
						</FormField>
					) }
				</>
			) }
		</div>
	);
}

function ConditionalEditor( { field, allFields, onChange } ) {
	const enabled = !! field.conditional;
	const others = allFields.filter( ( f ) => f.id !== field.id );

	function setConditional( patch ) {
		onChange( { conditional: { ...field.conditional, ...patch } } );
	}

	const sourceField = enabled ? others.find( ( f ) => f.id === field.conditional.field_id ) : null;

	return (
		<div className="am-flex am-flex-col am-gap-3">
			<label className="am-flex am-items-center am-gap-2 am-text-sm am-text-foreground">
				<Checkbox
					checked={ enabled }
					onChange={ ( e ) => onChange( { conditional: e.target.checked ? { field_id: others[ 0 ]?.id || '', operator: 'is', value: '' } : null } ) }
				/>
				Only show this field conditionally
			</label>

			{ enabled && ( others.length === 0 ? (
				<p className="am-text-xs am-text-muted-foreground">Add another field first to reference it here.</p>
			) : (
				<>
					<FormField label="Show this field if">
						<Select value={ field.conditional.field_id || others[ 0 ].id } onValueChange={ ( v ) => setConditional( { field_id: v } ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								{ others.map( ( f ) => <SelectItem key={ f.id } value={ f.id }>{ f.label || f.key }</SelectItem> ) }
							</SelectContent>
						</Select>
					</FormField>
					<FormField label="Condition">
						<Select value={ field.conditional.operator } onValueChange={ ( v ) => setConditional( { operator: v } ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="is">Is</SelectItem>
								<SelectItem value="is_not">Is Not</SelectItem>
							</SelectContent>
						</Select>
					</FormField>
					<FormField label="Value">
						{ sourceField && sourceField.options && sourceField.options.length > 0 ? (
							<Select value={ field.conditional.value } onValueChange={ ( v ) => setConditional( { value: v } ) }>
								<SelectTrigger><SelectValue /></SelectTrigger>
								<SelectContent>
									{ sourceField.options.map( ( o ) => <SelectItem key={ o.value } value={ o.value }>{ o.label }</SelectItem> ) }
								</SelectContent>
							</Select>
						) : (
							<Input value={ field.conditional.value } onChange={ ( e ) => setConditional( { value: e.target.value } ) } />
						) }
					</FormField>
				</>
			) ) }
		</div>
	);
}
