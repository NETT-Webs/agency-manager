import { useEffect, useState } from 'react';
import { FileText, Plus, Copy, Eye, Pencil, Trash2 } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { Skeleton } from '../components/ui/skeleton';
import { EmptyState } from '../components/ui/empty-state';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '../components/ui/dialog';
import { ConfirmDialog } from '../components/ui/confirm-dialog';
import { Input, FormField } from '../components/ui/form-controls';

const STATUS_LABELS = { publish: 'Published', draft: 'Draft', pending: 'Pending Review', private: 'Private' };
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../components/ui/select';
import { useToast } from '../components/ui/toast';
import { ShortcodesCard } from '../components/shortcodes-card';
import { getForms, getFormTemplates, createForm, duplicateForm, deleteForm } from '../lib/api';

function CopyChip( { text, toast } ) {
	return (
		<button
			type="button"
			onClick={ () => {
				navigator.clipboard.writeText( text ).then( () => toast( 'Shortcode copied.' ) ).catch( () => toast( 'Could not copy — copy it manually.', 'error' ) );
			} }
			className="am-inline-flex am-items-center am-gap-1 am-rounded am-bg-secondary am-px-2 am-py-1 am-font-mono am-text-xs am-text-foreground hover:am-bg-accent"
			title="Click to copy"
		>
			<Copy className="am-h-3 am-w-3" aria-hidden="true" /> { text }
		</button>
	);
}

function CreateFormDialog( { open, onOpenChange, onCreated } ) {
	const toast = useToast();
	const [ templates, setTemplates ] = useState( [] );
	const [ title, setTitle ] = useState( '' );
	const [ type, setType ] = useState( 'talent' );
	const [ template, setTemplate ] = useState( 'blank' );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		if ( open ) {
			getFormTemplates().then( setTemplates ).catch( () => {} );
		}
	}, [ open ] );

	function submit() {
		setSaving( true );
		createForm( { title, type, template } )
			.then( ( res ) => {
				toast( 'Form created.' );
				onOpenChange( false );
				setTitle( '' );
				onCreated( res );
			} )
			.catch( () => toast( 'Could not create the form.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	return (
		<Dialog open={ open } onOpenChange={ onOpenChange }>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Create a New Form</DialogTitle>
				</DialogHeader>
				<FormField label="Form Name" htmlFor="am-new-form-title">
					<Input id="am-new-form-title" value={ title } onChange={ ( e ) => setTitle( e.target.value ) } placeholder="e.g. Talent Application" />
				</FormField>
				<FormField label="Form Type" description="Controls which Applications tab submissions appear under, and which mapping targets are offered.">
					<Select value={ type } onValueChange={ setType }>
						<SelectTrigger><SelectValue /></SelectTrigger>
						<SelectContent>
							<SelectItem value="talent">Talent Application</SelectItem>
							<SelectItem value="location">Location Application</SelectItem>
							<SelectItem value="general">General / Contact</SelectItem>
						</SelectContent>
					</Select>
				</FormField>
				<FormField label="Start From" description="Templates create a normal, fully editable form — every field, label, and mapping can be changed afterward.">
					<Select value={ template } onValueChange={ setTemplate }>
						<SelectTrigger><SelectValue /></SelectTrigger>
						<SelectContent>
							<SelectItem value="blank">Blank Form</SelectItem>
							{ templates.map( ( t ) => <SelectItem key={ t.key } value={ t.key }>{ t.label }</SelectItem> ) }
						</SelectContent>
					</Select>
				</FormField>
				<DialogFooter>
					<DialogClose asChild><Button variant="outline" size="sm">Cancel</Button></DialogClose>
					<Button size="sm" onClick={ submit } disabled={ saving }>{ saving ? 'Creating…' : 'Create Form' }</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
}

export function Forms() {
	const toast = useToast();
	const [ forms, setForms ] = useState( null );
	const [ createOpen, setCreateOpen ] = useState( false );
	const [ pendingDelete, setPendingDelete ] = useState( null );
	const [ busyId, setBusyId ] = useState( null );

	function load() {
		setForms( null );
		getForms().then( setForms ).catch( () => toast( 'Could not load forms.', 'error' ) );
	}

	useEffect( load, [] );

	function handleDuplicate( id ) {
		setBusyId( id );
		duplicateForm( id )
			.then( () => { toast( 'Form duplicated.' ); load(); } )
			.catch( () => toast( 'Could not duplicate the form.', 'error' ) )
			.finally( () => setBusyId( null ) );
	}

	function handleDelete() {
		const id = pendingDelete;
		setBusyId( id );
		deleteForm( id )
			.then( () => { toast( 'Form moved to Trash.' ); load(); } )
			.catch( () => toast( 'Could not delete the form.', 'error' ) )
			.finally( () => { setBusyId( null ); setPendingDelete( null ); } );
	}

	const loading = ! forms;

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="Forms"
				description="Build the public application forms Talent and Locations use to apply to your agency."
				actions={ <Button size="sm" onClick={ () => setCreateOpen( true ) }><Plus className="am-h-4 am-w-4" aria-hidden="true" /> Create Form</Button> }
			/>

			<Card>
				<CardContent className="am-p-0">
					{ loading ? (
						<div className="am-flex am-flex-col am-gap-2 am-p-4">
							{ [ 0, 1 ].map( ( i ) => <Skeleton key={ i } className="am-h-10 am-w-full" /> ) }
						</div>
					) : forms.length === 0 ? (
						<EmptyState
							icon={ FileText }
							title="No forms yet"
							description="Create one above, or start from a template — every template is fully editable afterward."
							action={ <Button size="sm" onClick={ () => setCreateOpen( true ) }><Plus className="am-h-4 am-w-4" aria-hidden="true" /> Create Form</Button> }
							className="am-border-none"
						/>
					) : (
						<Table>
							<TableHeader>
								<TableRow>
									<TableHead>Form</TableHead>
									<TableHead>Type</TableHead>
									<TableHead>Status</TableHead>
									<TableHead>Fields</TableHead>
									<TableHead>Submissions</TableHead>
									<TableHead>Shortcode</TableHead>
									<TableHead className="am-text-right">Actions</TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								{ forms.map( ( form ) => (
									<TableRow key={ form.id }>
										<TableCell className="am-font-medium am-text-foreground">{ form.title }</TableCell>
										<TableCell className="am-text-muted-foreground">{ form.typeLabel }</TableCell>
										<TableCell><Badge variant={ 'publish' === form.status ? 'success' : 'secondary' }>{ STATUS_LABELS[ form.status ] || form.status }</Badge></TableCell>
										<TableCell className="am-text-muted-foreground">{ form.fieldCount }</TableCell>
										<TableCell>
											<a href={ form.applicationsUrl } className="am-text-primary hover:am-underline">{ form.submissionCount }</a>
										</TableCell>
										<TableCell><CopyChip text={ form.shortcode } toast={ toast } /></TableCell>
										<TableCell className="am-text-right">
											<div className="am-flex am-justify-end am-gap-1">
												<Button as="a" href={ form.editUrl } size="sm" variant="outline"><Pencil className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Edit</Button>
												<Button as="a" href={ form.previewUrl } target="_blank" rel="noopener" size="sm" variant="ghost"><Eye className="am-h-3.5 am-w-3.5" aria-hidden="true" /></Button>
												<Button size="sm" variant="ghost" disabled={ busyId === form.id } onClick={ () => handleDuplicate( form.id ) }><Copy className="am-h-3.5 am-w-3.5" aria-hidden="true" /></Button>
												<Button size="sm" variant="ghost" disabled={ busyId === form.id } onClick={ () => setPendingDelete( form.id ) }><Trash2 className="am-h-3.5 am-w-3.5" aria-hidden="true" /></Button>
											</div>
										</TableCell>
									</TableRow>
								) ) }
							</TableBody>
						</Table>
					) }
				</CardContent>
			</Card>

			<ShortcodesCard groupKeys={ [ 'forms' ] } title="Forms Shortcodes" />

			<CreateFormDialog open={ createOpen } onOpenChange={ setCreateOpen } onCreated={ ( res ) => { window.location.href = res.editUrl; } } />

			<ConfirmDialog
				open={ !! pendingDelete }
				onOpenChange={ ( open ) => ! open && setPendingDelete( null ) }
				title="Move this form to Trash?"
				description="You can restore it from Trash later if this was a mistake."
				destructive
				confirmLabel="Move to Trash"
				loading={ busyId === pendingDelete }
				onConfirm={ handleDelete }
			/>
		</div>
	);
}
