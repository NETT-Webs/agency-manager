import { Trash2 } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../ui/card';
import { Button } from '../ui/button';
import { Checkbox, FormField } from '../ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../ui/select';
import { StatusBadge } from '../ui/status-badge';

const STATUS_OPTIONS = [
	{ value: 'draft', label: 'Draft (not visible to visitors)' },
	{ value: 'publish', label: 'Published (visible on the website)' },
	{ value: 'pending', label: 'Pending Review' },
	{ value: 'private', label: 'Private (visible to admins only)' },
];

/**
 * Right-column status/visibility panel shared by the Talent and Location
 * editors — "Active"/"Featured" are the same `_am_active`/`_am_featured`
 * flags Cpt\Meta_Boxes' Visibility tab already writes, described here in
 * plain English instead of the internal field names.
 */
export function PublishPanel( { record, setField, saving, isNew, onSave, onSaveAndPreview, onCancel, onDelete, entityLabel } ) {
	return (
		<div className="am-flex am-flex-col am-gap-4">
			<Card>
				<CardHeader><CardTitle>Publish</CardTitle></CardHeader>
				<CardContent className="am-flex am-flex-col am-gap-4">
					<FormField label="Status">
						<Select value={ record.status } onValueChange={ ( v ) => setField( 'status', v ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								{ STATUS_OPTIONS.map( ( o ) => <SelectItem key={ o.value } value={ o.value }>{ o.label }</SelectItem> ) }
							</SelectContent>
						</Select>
					</FormField>

					<label className="am-flex am-items-center am-gap-2 am-text-sm">
						<Checkbox checked={ record.active } onChange={ ( e ) => setField( 'active', e.target.checked ) } />
						Active — shown in listings and shortcodes
					</label>
					<label className="am-flex am-items-center am-gap-2 am-text-sm">
						<Checkbox checked={ record.featured } onChange={ ( e ) => setField( 'featured', e.target.checked ) } />
						Featured — eligible for the homepage section
					</label>
					<label className="am-flex am-items-center am-gap-2 am-text-sm">
						<Checkbox checked={ record.homepage } onChange={ ( e ) => setField( 'homepage', e.target.checked ) } />
						Always show on homepage
					</label>

					<div className="am-flex am-flex-col am-gap-2 am-border-t am-border-border am-pt-4">
						<Button type="button" onClick={ onSave } disabled={ saving }>
							{ saving ? 'Saving…' : `Save ${ entityLabel }` }
						</Button>
						<Button type="button" variant="secondary" onClick={ onSaveAndPreview } disabled={ saving }>
							Save & Preview
						</Button>
						<Button type="button" variant="ghost" onClick={ onCancel } disabled={ saving }>
							Cancel
						</Button>
					</div>
				</CardContent>
			</Card>

			{ ! isNew && (
				<Card>
					<CardContent className="am-flex am-flex-col am-gap-3 am-p-4">
						<div className="am-flex am-items-center am-justify-between am-text-xs am-text-muted-foreground">
							<span>Last saved {record.editedAt}</span>
							<StatusBadge status={ record.status === 'publish' ? 'published' : record.status } />
						</div>
						<Button type="button" variant="outline" size="sm" onClick={ onDelete } className="am-text-destructive hover:am-text-destructive">
							<Trash2 className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Delete { entityLabel }
						</Button>
					</CardContent>
				</Card>
			) }
		</div>
	);
}
