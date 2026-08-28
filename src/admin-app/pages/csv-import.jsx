import { useEffect, useRef, useState } from 'react';
import { Upload, Users, MapPin, FileSpreadsheet, CheckCircle2, AlertTriangle, XCircle, ArrowLeft, ArrowRight, Download, Save, Trash2 } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Progress } from '../components/ui/progress';
import { Input, Checkbox, FormField, Label } from '../components/ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem, SelectGroup } from '../components/ui/select';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '../components/ui/dialog';
import { EmptyState } from '../components/ui/empty-state';
import { useToast } from '../components/ui/toast';
import {
	getCsvImportFields, uploadCsv, saveCsvMapping, previewCsvBatch, runCsvBatch,
	getCsvMappingTemplates, saveCsvMappingTemplate, deleteCsvMappingTemplate, links,
} from '../lib/api';

const STEPS = [ 'type', 'upload', 'map', 'review', 'importing', 'results' ];
const STEP_LABELS = { type: 'Import Type', upload: 'Upload', map: 'Map Columns', review: 'Review', importing: 'Importing', results: 'Results' };
const PREVIEW_BATCH = 25;

function Stepper( { step } ) {
	const idx = STEPS.indexOf( step );
	return (
		<div className="am-mb-6 am-flex am-items-center am-gap-2 am-text-xs am-font-medium am-text-muted-foreground">
			{ STEPS.map( ( s, i ) => (
				<div key={ s } className="am-flex am-items-center am-gap-2">
					<span className={ i <= idx ? 'am-text-primary' : '' }>{ i + 1 }. { STEP_LABELS[ s ] }</span>
					{ i < STEPS.length - 1 && <span className="am-text-border">→</span> }
				</div>
			) ) }
		</div>
	);
}

function StatusIcon( { status } ) {
	if ( 'error' === status ) return <XCircle className="am-h-4 am-w-4 am-text-destructive" aria-hidden="true" />;
	if ( 'warning' === status ) return <AlertTriangle className="am-h-4 am-w-4 am-text-amber-500" aria-hidden="true" />;
	return <CheckCircle2 className="am-h-4 am-w-4 am-text-green-600" aria-hidden="true" />;
}

// ---- Step 1: Import Type ----

function TypeStep( { onChoose } ) {
	return (
		<div className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
			<button type="button" onClick={ () => onChoose( 'talent' ) } className="am-text-left">
				<Card className="am-transition-colors hover:am-border-primary">
					<CardContent className="am-flex am-flex-col am-items-start am-gap-3 am-p-6">
						<Users className="am-h-8 am-w-8 am-text-primary" aria-hidden="true" />
						<div>
							<h3 className="am-font-semibold am-text-foreground">Talent</h3>
							<p className="am-mt-1 am-text-sm am-text-muted-foreground">Import a spreadsheet of Talent profiles.</p>
						</div>
					</CardContent>
				</Card>
			</button>
			<button type="button" onClick={ () => onChoose( 'location' ) } className="am-text-left">
				<Card className="am-transition-colors hover:am-border-primary">
					<CardContent className="am-flex am-flex-col am-items-start am-gap-3 am-p-6">
						<MapPin className="am-h-8 am-w-8 am-text-primary" aria-hidden="true" />
						<div>
							<h3 className="am-font-semibold am-text-foreground">Locations</h3>
							<p className="am-mt-1 am-text-sm am-text-muted-foreground">Import a spreadsheet of Location listings.</p>
						</div>
					</CardContent>
				</Card>
			</button>
		</div>
	);
}

// ---- Step 2: Upload ----

function UploadStep( { type, onUploaded, onBack } ) {
	const toast = useToast();
	const [ dragging, setDragging ] = useState( false );
	const [ uploading, setUploading ] = useState( false );
	const inputRef = useRef( null );

	function handleFile( file ) {
		if ( ! file ) return;
		if ( ! file.name.toLowerCase().endsWith( '.csv' ) ) {
			toast( 'Please choose a .csv file.', 'error' );
			return;
		}
		setUploading( true );
		uploadCsv( type, file )
			.then( onUploaded )
			.catch( ( err ) => toast( err?.message || 'Could not read that file.', 'error' ) )
			.finally( () => setUploading( false ) );
	}

	return (
		<Card>
			<CardContent className="am-p-6">
				<div
					onDragOver={ ( e ) => { e.preventDefault(); setDragging( true ); } }
					onDragLeave={ () => setDragging( false ) }
					onDrop={ ( e ) => { e.preventDefault(); setDragging( false ); handleFile( e.dataTransfer.files?.[ 0 ] ); } }
					className={ `am-flex am-flex-col am-items-center am-gap-3 am-rounded-lg am-border-2 am-border-dashed am-p-12 am-text-center am-transition-colors ${ dragging ? 'am-border-primary am-bg-accent' : 'am-border-border' }` }
				>
					<Upload className="am-h-8 am-w-8 am-text-muted-foreground" aria-hidden="true" />
					<p className="am-text-sm am-font-medium am-text-foreground">{ uploading ? 'Reading file…' : 'Drag and drop your CSV here' }</p>
					<p className="am-text-xs am-text-muted-foreground">or</p>
					<Button type="button" size="sm" variant="outline" disabled={ uploading } onClick={ () => inputRef.current?.click() }>
						Browse File
					</Button>
					<input ref={ inputRef } type="file" accept=".csv,text/csv" className="am-hidden" onChange={ ( e ) => handleFile( e.target.files?.[ 0 ] ) } />
					<p className="am-mt-2 am-text-xs am-text-muted-foreground">CSV files up to 20MB.</p>
				</div>
				<div className="am-mt-6 am-flex am-justify-start">
					<Button type="button" variant="ghost" size="sm" onClick={ onBack }><ArrowLeft className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Back</Button>
				</div>
			</CardContent>
		</Card>
	);
}

// ---- Step 3: Column Mapping ----

function MapStep( { type, session, fields, onContinue, onBack } ) {
	const toast = useToast();
	const [ columnMap, setColumnMap ] = useState( session.suggestedMap || {} );
	const [ options, setOptions ] = useState( session.options );
	const [ templates, setTemplates ] = useState( [] );
	const [ saveDialogOpen, setSaveDialogOpen ] = useState( false );
	const [ templateName, setTemplateName ] = useState( '' );

	useEffect( () => {
		getCsvMappingTemplates( type ).then( setTemplates ).catch( () => {} );
	}, [ type ] );

	const grouped = fields.reduce( ( acc, f ) => {
		( acc[ f.group ] = acc[ f.group ] || [] ).push( f );
		return acc;
	}, {} );
	const groupLabels = { core: 'Core', talent: 'Talent Fields', location: 'Location Fields', custom: 'Custom Fields' };

	function setTarget( column, targetKey ) {
		setColumnMap( ( prev ) => ( { ...prev, [ column ]: '__none__' === targetKey ? '' : targetKey } ) );
	}

	function loadTemplate( id ) {
		const tpl = templates.find( ( t ) => t.id === id );
		if ( tpl ) {
			setColumnMap( tpl.columnMap );
			toast( `Loaded mapping "${ tpl.name }".` );
		}
	}

	function saveTemplate() {
		if ( ! templateName.trim() ) return;
		saveCsvMappingTemplate( templateName.trim(), type, columnMap )
			.then( ( tpl ) => { setTemplates( ( prev ) => [ ...prev, tpl ] ); toast( 'Mapping saved.' ); setSaveDialogOpen( false ); setTemplateName( '' ); } )
			.catch( () => toast( 'Could not save this mapping.', 'error' ) );
	}

	function removeTemplate( id ) {
		deleteCsvMappingTemplate( id ).then( () => { setTemplates( ( prev ) => prev.filter( ( t ) => t.id !== id ) ); toast( 'Mapping deleted.' ); } );
	}

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<Card>
				<CardHeader className="am-flex-row am-items-center am-justify-between am-space-y-0">
					<CardTitle>Map Columns</CardTitle>
					<div className="am-flex am-items-center am-gap-2">
						{ templates.length > 0 && (
							<Select onValueChange={ loadTemplate }>
								<SelectTrigger className="am-w-48"><SelectValue placeholder="Load saved mapping…" /></SelectTrigger>
								<SelectContent>
									{ templates.map( ( t ) => <SelectItem key={ t.id } value={ t.id }>{ t.name }</SelectItem> ) }
								</SelectContent>
							</Select>
						) }
						<Button type="button" size="sm" variant="outline" onClick={ () => setSaveDialogOpen( true ) }><Save className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Save Mapping</Button>
					</div>
				</CardHeader>
				<CardContent>
					<p className="am-mb-4 am-text-sm am-text-muted-foreground">Every spreadsheet column below can be mapped to an Agency Manager field, or left as "Don't import."</p>
					<div className="am-flex am-flex-col am-divide-y am-divide-border am-rounded-md am-border am-border-border">
						{ session.columns.map( ( column ) => (
							<div key={ column } className="am-grid am-grid-cols-[1fr_auto_1fr] am-items-center am-gap-3 am-p-3">
								<div className="am-truncate am-text-sm am-font-medium am-text-foreground">{ column }</div>
								<ArrowRight className="am-h-4 am-w-4 am-text-muted-foreground" aria-hidden="true" />
								<Select value={ columnMap[ column ] || '__none__' } onValueChange={ ( v ) => setTarget( column, v ) }>
									<SelectTrigger><SelectValue /></SelectTrigger>
									<SelectContent>
										<SelectItem value="__none__">Don't import</SelectItem>
										{ Object.entries( grouped ).map( ( [ group, items ] ) => (
											<SelectGroup key={ group }>
												{ items.map( ( f ) => <SelectItem key={ f.key } value={ f.key }>{ f.label }</SelectItem> ) }
											</SelectGroup>
										) ) }
									</SelectContent>
								</Select>
							</div>
						) ) }
					</div>
				</CardContent>
			</Card>

			<Card>
				<CardHeader><CardTitle>Import Options</CardTitle></CardHeader>
				<CardContent className="am-flex am-flex-col am-gap-4">
					<label className="am-flex am-items-center am-gap-2 am-text-sm">
						<Checkbox checked={ options.createTerms } onChange={ ( e ) => setOptions( { ...options, createTerms: e.target.checked } ) } />
						Create missing categories/groups/types automatically
					</label>
					<label className="am-flex am-items-center am-gap-2 am-text-sm">
						<Checkbox checked={ options.importImages } onChange={ ( e ) => setOptions( { ...options, importImages: e.target.checked } ) } />
						Import images from URLs in the spreadsheet
					</label>

					<FormField label="When a matching record already exists">
						<div className="am-flex am-flex-col am-gap-2">
							{ [
								{ value: 'create', label: 'Create a new record anyway' },
								{ value: 'update', label: 'Update the existing record (fields not in this CSV are preserved)' },
								{ value: 'skip', label: 'Skip it' },
							].map( ( o ) => (
								<label key={ o.value } className="am-flex am-items-center am-gap-2 am-text-sm">
									<input type="radio" checked={ options.duplicateMode === o.value } onChange={ () => setOptions( { ...options, duplicateMode: o.value } ) } />
									{ o.label }
								</label>
							) ) }
						</div>
					</FormField>

					<FormField label="Match existing records by" description="Never matched by name alone.">
						<Select value={ options.matchField } onValueChange={ ( v ) => setOptions( { ...options, matchField: v } ) }>
							<SelectTrigger className="am-w-64"><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="email">Email address</SelectItem>
								<SelectItem value="id">WordPress ID (a CSV column literally named "id")</SelectItem>
								<SelectItem value="title">Name (only if you understand the risk of same-name records)</SelectItem>
							</SelectContent>
						</Select>
					</FormField>
				</CardContent>
			</Card>

			<div className="am-flex am-justify-between">
				<Button type="button" variant="ghost" onClick={ onBack }><ArrowLeft className="am-h-4 am-w-4" aria-hidden="true" /> Back</Button>
				<Button type="button" onClick={ () => onContinue( columnMap, options ) }>
					Continue to Review <ArrowRight className="am-h-4 am-w-4" aria-hidden="true" />
				</Button>
			</div>

			<Dialog open={ saveDialogOpen } onOpenChange={ setSaveDialogOpen }>
				<DialogContent>
					<DialogHeader><DialogTitle>Save Mapping As</DialogTitle></DialogHeader>
					<FormField label="Mapping name">
						<Input value={ templateName } onChange={ ( e ) => setTemplateName( e.target.value ) } placeholder="e.g. Agency Casting CSV" />
					</FormField>
					{ templates.length > 0 && (
						<div className="am-mt-2 am-flex am-flex-col am-gap-1">
							<Label>Saved mappings</Label>
							{ templates.map( ( t ) => (
								<div key={ t.id } className="am-flex am-items-center am-justify-between am-rounded am-border am-border-border am-px-2 am-py-1 am-text-sm">
									{ t.name }
									<Button type="button" size="sm" variant="ghost" onClick={ () => removeTemplate( t.id ) }><Trash2 className="am-h-3.5 am-w-3.5" aria-hidden="true" /></Button>
								</div>
							) ) }
						</div>
					) }
					<DialogFooter>
						<DialogClose asChild><Button variant="outline" size="sm">Cancel</Button></DialogClose>
						<Button size="sm" onClick={ saveTemplate }>Save</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
		</div>
	);
}

// ---- Step 4: Review ----

function ReviewStep( { session, onStartImport, onBack, loading, rows, summary } ) {
	const [ showIssuesOnly, setShowIssuesOnly ] = useState( false );
	const visibleRows = showIssuesOnly ? rows.filter( ( r ) => 'ok' !== r.status ) : rows;

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<Card>
				<CardHeader><CardTitle>Import Preview</CardTitle></CardHeader>
				<CardContent>
					{ loading ? (
						<p className="am-text-sm am-text-muted-foreground">Validating { session.rowCount } records…</p>
					) : (
						<>
							<div className="am-mb-4 am-grid am-grid-cols-2 am-gap-3 sm:am-grid-cols-4">
								<Card><CardContent className="am-p-3 am-text-center"><div className="am-text-xl am-font-semibold">{ summary.total }</div><div className="am-text-xs am-text-muted-foreground">Records detected</div></CardContent></Card>
								<Card><CardContent className="am-p-3 am-text-center"><div className="am-text-xl am-font-semibold am-text-green-600">{ summary.valid }</div><div className="am-text-xs am-text-muted-foreground">Valid</div></CardContent></Card>
								<Card><CardContent className="am-p-3 am-text-center"><div className="am-text-xl am-font-semibold am-text-amber-500">{ summary.warnings }</div><div className="am-text-xs am-text-muted-foreground">Warnings</div></CardContent></Card>
								<Card><CardContent className="am-p-3 am-text-center"><div className="am-text-xl am-font-semibold am-text-destructive">{ summary.errors }</div><div className="am-text-xs am-text-muted-foreground">Errors</div></CardContent></Card>
							</div>
							<p className="am-mb-3 am-text-sm am-text-muted-foreground">{ summary.existing } record{ 1 === summary.existing ? '' : 's' } appear to already exist; { summary.total - summary.existing } would be new.</p>
							<Button type="button" size="sm" variant="outline" className="am-mb-3" onClick={ () => setShowIssuesOnly( ( v ) => ! v ) }>
								{ showIssuesOnly ? 'Show All Rows' : 'View Issues' }
							</Button>
							<Table>
								<TableHeader>
									<TableRow><TableHead className="am-w-8" /><TableHead>Row</TableHead><TableHead>Name</TableHead><TableHead>Note</TableHead></TableRow>
								</TableHeader>
								<TableBody>
									{ visibleRows.slice( 0, 100 ).map( ( r ) => (
										<TableRow key={ r.row }>
											<TableCell><StatusIcon status={ r.status } /></TableCell>
											<TableCell>{ r.row }</TableCell>
											<TableCell>{ r.name || '—' }{ r.existingId > 0 && <Badge variant="outline" className="am-ml-2">existing</Badge> }</TableCell>
											<TableCell className="am-text-xs am-text-muted-foreground">{ [ ...r.errors, ...r.warnings ].join( ' ' ) }</TableCell>
										</TableRow>
									) ) }
								</TableBody>
							</Table>
						</>
					) }
				</CardContent>
			</Card>
			<div className="am-flex am-justify-between">
				<Button type="button" variant="ghost" onClick={ onBack } disabled={ loading }><ArrowLeft className="am-h-4 am-w-4" aria-hidden="true" /> Back</Button>
				<Button type="button" onClick={ onStartImport } disabled={ loading || 0 === summary.valid + summary.warnings }>Start Import</Button>
			</div>
		</div>
	);
}

// ---- Step 5: Importing ----

function ImportingStep( { processed, total } ) {
	const pct = total > 0 ? Math.round( ( processed / total ) * 100 ) : 0;
	return (
		<Card>
			<CardContent className="am-flex am-flex-col am-items-center am-gap-4 am-p-10 am-text-center">
				<FileSpreadsheet className="am-h-8 am-w-8 am-text-primary" aria-hidden="true" />
				<p className="am-font-medium am-text-foreground">Importing…</p>
				<Progress value={ pct } className="am-max-w-sm" />
				<p className="am-text-sm am-text-muted-foreground">{ processed } / { total }</p>
			</CardContent>
		</Card>
	);
}

// ---- Step 6: Results ----

function ResultsStep( { type, counts, results, onRestart } ) {
	function downloadReport() {
		// CSV formula-injection guard: a cell whose *rendered* value begins
		// with =, +, -, @, tab, or CR would run as a formula in Excel/Sheets
		// when this report is later opened — imported row values (Name,
		// Reason) came straight from someone else's spreadsheet, so they're
		// untrusted here even though they're safe as plain WordPress data.
		// Prefixing with a bare apostrophe is the standard OWASP mitigation;
		// it's invisible in the spreadsheet UI, only forces text interpretation.
		function csvCell( value ) {
			let str = String( value ?? '' );
			if ( /^[=+\-@\t\r]/.test( str ) ) {
				str = `'${ str }`;
			}
			return `"${ str.replace( /"/g, '""' ) }"`;
		}
		const header = [ 'Row', 'Name', 'Action', 'Status', 'Reason' ];
		const lines = [ header.join( ',' ) ].concat(
			results.map( ( r ) => [ r.row, r.name, r.action, r.status, r.reason ].map( csvCell ).join( ',' ) )
		);
		const blob = new Blob( [ lines.join( '\n' ) ], { type: 'text/csv' } );
		const url = URL.createObjectURL( blob );
		const a = document.createElement( 'a' );
		a.href = url;
		a.download = 'agency-manager-import-report.csv';
		a.click();
		URL.revokeObjectURL( url );
	}

	return (
		<Card>
			<CardContent className="am-flex am-flex-col am-items-center am-gap-4 am-p-10 am-text-center">
				<CheckCircle2 className="am-h-10 am-w-10 am-text-green-600" aria-hidden="true" />
				<h3 className="am-text-lg am-font-semibold">Import Complete</h3>
				<p className="am-text-sm am-text-muted-foreground">{ counts.created + counts.updated + counts.skipped + counts.errors } processed</p>
				<div className="am-grid am-grid-cols-2 am-gap-3 sm:am-grid-cols-4">
					<Badge variant="success">{ counts.created } created</Badge>
					<Badge variant="secondary">{ counts.updated } updated</Badge>
					<Badge variant="outline">{ counts.skipped } skipped</Badge>
					<Badge variant={ counts.errors ? 'destructive' : 'outline' }>{ counts.errors } errors</Badge>
				</div>
				<div className="am-mt-4 am-flex am-flex-wrap am-justify-center am-gap-2">
					<Button as="a" href={ 'talent' === type ? links.talent : links.locations } size="sm">
						View Imported { 'talent' === type ? 'Talent' : 'Locations' }
					</Button>
					<Button type="button" size="sm" variant="outline" onClick={ downloadReport }><Download className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Download Import Report</Button>
					<Button type="button" size="sm" variant="ghost" onClick={ onRestart }>Import Another File</Button>
				</div>
			</CardContent>
		</Card>
	);
}

// ---- Wizard ----

export function CsvImport() {
	const toast = useToast();
	const [ step, setStep ] = useState( 'type' );
	const [ importType, setImportType ] = useState( 'talent' );
	const [ fields, setFields ] = useState( [] );
	const [ session, setSession ] = useState( null );
	const [ reviewLoading, setReviewLoading ] = useState( false );
	const [ reviewRows, setReviewRows ] = useState( [] );
	const [ summary, setSummary ] = useState( { total: 0, valid: 0, warnings: 0, errors: 0, existing: 0 } );
	const [ importProgress, setImportProgress ] = useState( { processed: 0 } );
	const [ finalCounts, setFinalCounts ] = useState( { created: 0, updated: 0, skipped: 0, errors: 0 } );
	const [ finalResults, setFinalResults ] = useState( [] );

	function chooseType( type ) {
		setImportType( type );
		getCsvImportFields( type ).then( setFields ).catch( () => toast( 'Could not load field list.', 'error' ) );
		setStep( 'upload' );
	}

	function onUploaded( newSession ) {
		setSession( newSession );
		setStep( 'map' );
	}

	function onMapContinue( columnMap, options ) {
		saveCsvMapping( session.id, columnMap, options )
			.then( ( updated ) => {
				setSession( updated );
				setStep( 'review' );
				runPreview( updated );
			} )
			.catch( () => toast( 'Could not save the column mapping.', 'error' ) );
	}

	function runPreview( sess ) {
		setReviewLoading( true );
		setReviewRows( [] );
		const total = sess.rowCount;
		let offset = 0;
		const allRows = [];

		function next() {
			previewCsvBatch( sess.id, offset, PREVIEW_BATCH ).then( ( res ) => {
				allRows.push( ...res.rows );
				offset += PREVIEW_BATCH;
				if ( offset < total ) {
					next();
				} else {
					setReviewRows( allRows );
					setSummary( {
						total: allRows.length,
						valid: allRows.filter( ( r ) => 'ok' === r.status ).length,
						warnings: allRows.filter( ( r ) => 'warning' === r.status ).length,
						errors: allRows.filter( ( r ) => 'error' === r.status ).length,
						existing: allRows.filter( ( r ) => r.existingId > 0 ).length,
					} );
					setReviewLoading( false );
				}
			} ).catch( () => { toast( 'Could not validate the file.', 'error' ); setReviewLoading( false ); } );
		}
		next();
	}

	function startImport() {
		setStep( 'importing' );
		const total = session.rowCount;
		let offset = 0;
		const allResults = [];
		const counts = { created: 0, updated: 0, skipped: 0, errors: 0 };

		function next() {
			runCsvBatch( session.id, offset, PREVIEW_BATCH ).then( ( res ) => {
				allResults.push( ...res.results );
				counts.created += res.created;
				counts.updated += res.updated;
				counts.skipped += res.skipped;
				counts.errors += res.errors;
				offset += PREVIEW_BATCH;
				setImportProgress( { processed: Math.min( offset, total ) } );
				if ( offset < total ) {
					next();
				} else {
					setFinalCounts( counts );
					setFinalResults( allResults );
					setStep( 'results' );
				}
			} ).catch( () => toast( 'Import stopped due to an error — see what completed in the report.', 'error' ) );
		}
		next();
	}

	function restart() {
		setStep( 'type' );
		setSession( null );
		setReviewRows( [] );
		setImportProgress( { processed: 0 } );
	}

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="CSV Import"
				description="Bring in Talent or Locations from a spreadsheet — map columns, preview, and import."
				breadcrumbs={ [ { label: 'Import / Export', href: links.importExport }, { label: 'CSV Import' } ] }
			/>
			<Stepper step={ step } />

			{ 'type' === step && <TypeStep onChoose={ chooseType } /> }
			{ 'upload' === step && <UploadStep type={ importType } onUploaded={ onUploaded } onBack={ () => setStep( 'type' ) } /> }
			{ 'map' === step && session && <MapStep type={ importType } session={ session } fields={ fields } onContinue={ onMapContinue } onBack={ () => setStep( 'upload' ) } /> }
			{ 'review' === step && session && (
				<ReviewStep session={ session } loading={ reviewLoading } rows={ reviewRows } summary={ summary } onStartImport={ startImport } onBack={ () => setStep( 'map' ) } />
			) }
			{ 'importing' === step && <ImportingStep processed={ importProgress.processed } total={ session?.rowCount || 0 } /> }
			{ 'results' === step && <ResultsStep type={ importType } counts={ finalCounts } results={ finalResults } onRestart={ restart } /> }
		</div>
	);
}
