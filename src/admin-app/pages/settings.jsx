import { useEffect, useState } from 'react';
import { Download, Upload } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Skeleton } from '../components/ui/skeleton';
import { Input, FormField } from '../components/ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../components/ui/select';
import { useToast } from '../components/ui/toast';
import { getSettings, saveSettings } from '../lib/api';

const AGENCY_TYPES = [
	{ value: 'talent', label: 'Talent Agency' },
	{ value: 'location', label: 'Location Agency' },
	{ value: 'casting', label: 'Casting Agency' },
	{ value: 'model', label: 'Model Agency' },
	{ value: 'both', label: 'Combined Agency' },
];

const BACKUP_SECTIONS = [ 'plugin_settings', 'display_settings', 'homepage_settings', 'forms' ];
const config = window.amAdminApp?.importExport || {};

function BackupCard() {
	return (
		<Card>
			<CardHeader><CardTitle>Backup & Restore</CardTitle></CardHeader>
			<CardContent className="am-flex am-flex-col am-gap-4">
				<p className="am-text-sm am-text-muted-foreground">A one-click backup of just your Settings and Forms (not Talent/Location content) — useful before making big configuration changes.</p>
				<Button
					as="a"
					size="sm"
					variant="outline"
					href={ `${ config.adminPostUrl }?action=am_export&_wpnonce=${ config.exportNonce }&${ BACKUP_SECTIONS.map( ( s ) => `sections%5B%5D=${ s }` ).join( '&' ) }` }
				>
					<Download className="am-h-4 am-w-4" aria-hidden="true" /> Backup Settings
				</Button>
				<form method="post" action={ config.adminPostUrl } encType="multipart/form-data" className="am-flex am-flex-wrap am-items-center am-gap-2">
					<input type="hidden" name="action" value="am_import" />
					<input type="hidden" name="am_import_nonce" value={ config.importNonce } />
					{ BACKUP_SECTIONS.map( ( s ) => <input key={ s } type="hidden" name="sections[]" value={ s } /> ) }
					<input type="file" name="am_import_file" accept="application/json" className="am-text-sm" />
					<Button type="submit" size="sm" variant="outline"><Upload className="am-h-4 am-w-4" aria-hidden="true" /> Restore Settings</Button>
				</form>
			</CardContent>
		</Card>
	);
}

export function SettingsPage() {
	const toast = useToast();
	const [ state, setState ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		getSettings().then( setState ).catch( () => toast( 'Could not load settings.', 'error' ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	function save() {
		setSaving( true );
		saveSettings( state )
			.then( () => toast( 'Settings saved.' ) )
			.catch( () => toast( 'Could not save settings.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	if ( ! state ) {
		return (
			<div className="am-flex am-flex-col am-gap-4">
				<Skeleton className="am-h-8 am-w-64" />
				<Skeleton className="am-h-48 am-w-full" />
			</div>
		);
	}

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="Settings"
				description="General agency configuration."
				actions={ <Button size="sm" onClick={ save } disabled={ saving }>{ saving ? 'Saving…' : 'Save Settings' }</Button> }
			/>

			<Card>
				<CardHeader><CardTitle>Agency</CardTitle></CardHeader>
				<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
					<FormField label="Agency Type">
						<Select value={ state.agency_type } onValueChange={ ( v ) => setState( { ...state, agency_type: v } ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								{ AGENCY_TYPES.map( ( t ) => <SelectItem key={ t.value } value={ t.value }>{ t.label }</SelectItem> ) }
							</SelectContent>
						</Select>
					</FormField>
					<FormField label="Notification Email">
						<Input type="email" value={ state.notification_email } onChange={ ( e ) => setState( { ...state, notification_email: e.target.value } ) } />
					</FormField>
				</CardContent>
			</Card>

			<BackupCard />
		</div>
	);
}
