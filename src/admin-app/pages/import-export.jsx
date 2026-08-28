import { useEffect, useRef, useState } from 'react';
import { Download, Upload, CheckCircle2, AlertTriangle, FileSpreadsheet, ArrowRight } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Checkbox, Label } from '../components/ui/form-controls';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import apiFetch from '@wordpress/api-fetch';
import { links } from '../lib/api';

const SECTION_LABELS = {
	talent: 'Talent',
	locations: 'Locations',
	categories: 'Talent Categories',
	groups: 'Talent Groups',
	location_types: 'Location Categories',
	display_settings: 'Website Display Settings',
	homepage_settings: 'Homepage Featured Settings',
	widget_style_presets: 'Widget Style Presets',
	plugin_settings: 'Plugin Settings',
	forms: 'Forms',
};

const CONTENT_KEYS = [ 'talent', 'locations', 'categories', 'groups', 'location_types' ];
const SETTINGS_KEYS = [ 'display_settings', 'homepage_settings', 'widget_style_presets', 'plugin_settings' ];
const OPTIONAL_KEYS = [ 'forms' ];

const CONTENT_QUICK = [ 'talent', 'locations', 'categories', 'groups', 'location_types', 'display_settings', 'homepage_settings', 'widget_style_presets' ];
const EVERYTHING_QUICK = [ ...CONTENT_QUICK, 'forms', 'plugin_settings' ];

const config = window.amAdminApp?.importExport || {};

function CheckboxGroups( { selected, onToggle } ) {
	const groups = [
		[ 'Content', CONTENT_KEYS ],
		[ 'Settings', SETTINGS_KEYS ],
		[ 'Optional', OPTIONAL_KEYS ],
	];

	return (
		<div className="am-flex am-flex-col am-gap-4">
			{ groups.map( ( [ label, keys ] ) => (
				<div key={ label }>
					<h4 className="am-mb-2 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">{ label }</h4>
					<div className="am-flex am-flex-col am-gap-2">
						{ keys.map( ( key ) => (
							<label key={ key } className="am-flex am-items-center am-gap-2 am-text-sm">
								<Checkbox checked={ selected.includes( key ) } onChange={ () => onToggle( key ) } name="sections[]" value={ key } />
								{ SECTION_LABELS[ key ] }
								{ OPTIONAL_KEYS.includes( key ) && <span className="am-text-xs am-text-muted-foreground">(optional)</span> }
							</label>
						) ) }
					</div>
				</div>
			) ) }
		</div>
	);
}

function ExportCard() {
	const [ selected, setSelected ] = useState( [] );
	const formRef = useRef( null );

	function toggle( key ) {
		setSelected( ( prev ) => ( prev.includes( key ) ? prev.filter( ( k ) => k !== key ) : [ ...prev, key ] ) );
	}

	function quickExport( keys ) {
		setSelected( keys );
		// Submit on next tick so the checkbox state above is reflected in the DOM before native form submission.
		setTimeout( () => formRef.current?.submit(), 0 );
	}

	return (
		<Card>
			<CardHeader>
				<CardTitle>Export</CardTitle>
			</CardHeader>
			<CardContent className="am-flex am-flex-col am-gap-4">
				<p className="am-text-sm am-text-muted-foreground">
					Move the reusable parts of this agency to another WordPress installation: Talent, Locations, their Categories/Groups, and the Display Settings that control how they appear.
				</p>
				<form ref={ formRef } method="get" action={ config.adminPostUrl }>
					<input type="hidden" name="action" value="am_export" />
					<input type="hidden" name="_wpnonce" value={ config.exportNonce } />
					{ selected.map( ( key ) => <input key={ key } type="hidden" name="sections[]" value={ key } /> ) }
					<CheckboxGroups selected={ selected } onToggle={ toggle } />
					<Button type="submit" size="sm" className="am-mt-4">
						<Download className="am-h-4 am-w-4" aria-hidden="true" /> Export Selected
					</Button>
				</form>
				<div className="am-border-t am-border-border am-pt-4">
					<h4 className="am-mb-2 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">Quick Actions</h4>
					<div className="am-flex am-flex-wrap am-gap-2">
						<Button size="sm" variant="outline" onClick={ () => quickExport( [ 'talent' ] ) }>Export Talent</Button>
						<Button size="sm" variant="outline" onClick={ () => quickExport( [ 'locations' ] ) }>Export Locations</Button>
						<Button size="sm" variant="outline" onClick={ () => quickExport( CONTENT_QUICK ) }>Export Content</Button>
						<Button size="sm" variant="secondary" onClick={ () => quickExport( EVERYTHING_QUICK ) }>Export Everything</Button>
					</div>
					<p className="am-mt-2 am-text-xs am-text-muted-foreground">Each quick action selects its sections and exports immediately — no manual ticking required.</p>
				</div>
			</CardContent>
		</Card>
	);
}

function ImportCard() {
	const [ selected, setSelected ] = useState( [ ...CONTENT_KEYS, ...SETTINGS_KEYS ] );
	const [ fileName, setFileName ] = useState( '' );

	function toggle( key ) {
		setSelected( ( prev ) => ( prev.includes( key ) ? prev.filter( ( k ) => k !== key ) : [ ...prev, key ] ) );
	}

	return (
		<Card>
			<CardHeader>
				<CardTitle>Import</CardTitle>
			</CardHeader>
			<CardContent className="am-flex am-flex-col am-gap-4">
				<p className="am-text-sm am-text-muted-foreground">
					Upload the same JSON file. Existing Talent, Locations, Categories, Groups, and Forms are matched by slug and updated in place — nothing is duplicated.
				</p>
				<form method="post" action={ config.adminPostUrl } encType="multipart/form-data">
					<input type="hidden" name="action" value="am_import" />
					<input type="hidden" name="am_import_nonce" value={ config.importNonce } />
					<div className="am-mb-4">
						<Label htmlFor="am_import_file">JSON File</Label>
						<input
							id="am_import_file"
							type="file"
							name="am_import_file"
							accept="application/json"
							onChange={ ( e ) => setFileName( e.target.files?.[ 0 ]?.name || '' ) }
							className="am-block am-text-sm"
						/>
						{ fileName && <p className="am-mt-1 am-text-xs am-text-muted-foreground">{ fileName }</p> }
					</div>
					<CheckboxGroups selected={ selected } onToggle={ toggle } />
					<p className="am-mt-2 am-text-xs am-text-muted-foreground">Only sections actually present in the uploaded file are applied.</p>
					<Button type="submit" size="sm" className="am-mt-4">
						<Upload className="am-h-4 am-w-4" aria-hidden="true" /> Import
					</Button>
				</form>
			</CardContent>
		</Card>
	);
}

function ReportCard() {
	const params = new URLSearchParams( window.location.search );
	const imported = params.has( 'am_imported' );
	const importError = params.has( 'am_import_error' );
	const [ report, setReport ] = useState( null );

	useEffect( () => {
		if ( imported ) {
			apiFetch( { path: '/agency-manager/v1/import-export/report' } ).then( setReport ).catch( () => {} );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	if ( ! imported && ! importError ) {
		return null;
	}

	return (
		<Card>
			<CardContent className="am-flex am-flex-col am-gap-3 am-p-4">
				{ imported && (
					<div className="am-flex am-items-center am-gap-2 am-text-sm am-text-green-700">
						<CheckCircle2 className="am-h-4 am-w-4" aria-hidden="true" /> Import complete — see the report below.
					</div>
				) }
				{ importError && (
					<div className="am-flex am-items-center am-gap-2 am-text-sm am-text-destructive">
						<AlertTriangle className="am-h-4 am-w-4" aria-hidden="true" /> Import failed — check the file and try again.
					</div>
				) }
				{ report && Object.keys( report ).length > 0 && (
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>Section</TableHead>
								<TableHead>Created</TableHead>
								<TableHead>Updated</TableHead>
								<TableHead>Errors</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							{ Object.entries( report ).map( ( [ section, counts ] ) => (
								<TableRow key={ section }>
									<TableCell>{ SECTION_LABELS[ section ] || section }</TableCell>
									<TableCell>{ counts.created ?? '—' }</TableCell>
									<TableCell>{ counts.updated ?? '—' }</TableCell>
									<TableCell>{ counts.errors ?? 0 }</TableCell>
								</TableRow>
							) ) }
						</TableBody>
					</Table>
				) }
			</CardContent>
		</Card>
	);
}

export function ImportExport() {
	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="Import / Export"
				description="Move Talent, Locations, and site configuration to or from another WordPress installation."
			/>
			<ReportCard />

			<Card>
				<CardContent className="am-flex am-flex-col am-items-start am-gap-3 am-p-4 sm:am-flex-row sm:am-items-center sm:am-justify-between">
					<div className="am-flex am-items-center am-gap-3">
						<FileSpreadsheet className="am-h-6 am-w-6 am-text-primary" aria-hidden="true" />
						<div>
							<h3 className="am-text-sm am-font-semibold am-text-foreground">CSV Import</h3>
							<p className="am-text-xs am-text-muted-foreground">Bring in Talent or Locations from a spreadsheet — map columns, preview, and import.</p>
						</div>
					</div>
					<Button as="a" href={ links.csvImport } size="sm">Open CSV Import <ArrowRight className="am-h-3.5 am-w-3.5" aria-hidden="true" /></Button>
				</CardContent>
			</Card>

			<div className="am-grid am-grid-cols-1 am-gap-6 lg:am-grid-cols-2">
				<ExportCard />
				<ImportCard />
			</div>
		</div>
	);
}
