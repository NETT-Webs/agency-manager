import { Shell } from './components/shell/shell';
import { Dashboard } from './pages/dashboard';
import { Applications } from './pages/applications';
import { Forms } from './pages/forms';
import { Talent } from './pages/talent';
import { Locations } from './pages/locations';
import { Display } from './pages/display';
import { ImportExport } from './pages/import-export';
import { SettingsPage } from './pages/settings';
import { TalentEditor } from './pages/talent-editor';
import { LocationEditor } from './pages/location-editor';
import { CsvImport } from './pages/csv-import';
import { FormBuilder } from './pages/form-builder';
import { screen } from './lib/api';

const TITLES = {
	dashboard: 'Dashboard',
	talent: 'Talent',
	locations: 'Locations',
	applications: 'Applications',
	forms: 'Forms',
	display: 'Website Display',
	'import-export': 'Import / Export',
	settings: 'Settings',
	'talent-edit': 'Talent',
	'location-edit': 'Locations',
	'csv-import': 'CSV Import',
	'form-builder': 'Forms',
};

const PAGES = {
	dashboard: Dashboard,
	applications: Applications,
	forms: Forms,
	talent: Talent,
	locations: Locations,
	display: Display,
	'import-export': ImportExport,
	settings: SettingsPage,
	'talent-edit': TalentEditor,
	'location-edit': LocationEditor,
	'csv-import': CsvImport,
	'form-builder': FormBuilder,
};

// Screens that need the entire content area instead of the default
// centered/padded column — see Shell's `fullBleed` prop.
const FULL_BLEED_SCREENS = [ 'form-builder' ];

/**
 * One bundle, one PHP mount point per screen (see Admin_App_Page) — the
 * localized `screen` value (from the current admin-menu URL) picks which
 * page component renders inside the shared Shell. Screens not yet migrated
 * fall back to a short notice; they're still reachable as normal PHP pages
 * from the sidebar until their own phase lands.
 */
export function App() {
	const Page = PAGES[ screen ] || ComingSoon;
	return (
		<Shell title={ TITLES[ screen ] || 'Agency Manager' } fullBleed={ FULL_BLEED_SCREENS.includes( screen ) }>
			<Page />
		</Shell>
	);
}

function ComingSoon() {
	return (
		<div className="am-rounded-lg am-border am-border-dashed am-border-border am-p-10 am-text-center am-text-sm am-text-muted-foreground">
			This screen hasn't been migrated to the new design yet.
		</div>
	);
}
