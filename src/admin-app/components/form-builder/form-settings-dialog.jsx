import { Dialog, DialogContent, DialogHeader, DialogTitle } from '../ui/dialog';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../ui/tabs';
import { Input, Textarea, FormField } from '../ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../ui/select';

const TYPE_OPTIONS = [
	{ value: 'talent', label: 'Talent Application' },
	{ value: 'location', label: 'Location Application' },
	{ value: 'general', label: 'General / Contact' },
];

/**
 * Form-level settings, separated from per-field settings — the same three
 * values Form_Builder_Page's toolbar always edited (title, type,
 * confirmation message), just grouped by purpose instead of sitting in one
 * inline bar. "Application" governs which Applications tab submissions
 * land on and which Talent/Location mapping targets are offered (see
 * Mapping_Targets) — there's no separate backend "security" setting at the
 * form level, so no Security tab is invented here.
 */
export function FormSettingsDialog( { open, onOpenChange, title, type, confirmation, onChange } ) {
	return (
		<Dialog open={ open } onOpenChange={ onOpenChange }>
			<DialogContent className="am-max-w-lg">
				<DialogHeader>
					<DialogTitle>Form Settings</DialogTitle>
				</DialogHeader>
				<Tabs defaultValue="general">
					<TabsList>
						<TabsTrigger value="general">General</TabsTrigger>
						<TabsTrigger value="application">Application</TabsTrigger>
						<TabsTrigger value="submission">Submission</TabsTrigger>
					</TabsList>

					<TabsContent value="general">
						<FormField label="Form Name" htmlFor="am-fset-title">
							<Input id="am-fset-title" value={ title } onChange={ ( e ) => onChange( { title: e.target.value } ) } />
						</FormField>
					</TabsContent>

					<TabsContent value="application">
						<FormField
							label="Form Type"
							description="Controls which Applications tab submissions appear under, and which mapping targets are offered on each field."
						>
							<Select value={ type } onValueChange={ ( v ) => onChange( { type: v } ) }>
								<SelectTrigger><SelectValue /></SelectTrigger>
								<SelectContent>
									{ TYPE_OPTIONS.map( ( opt ) => <SelectItem key={ opt.value } value={ opt.value }>{ opt.label }</SelectItem> ) }
								</SelectContent>
							</Select>
						</FormField>
					</TabsContent>

					<TabsContent value="submission">
						<FormField label="Confirmation Message" htmlFor="am-fset-confirm" description="Shown to visitors after they submit this form.">
							<Textarea id="am-fset-confirm" rows={ 4 } value={ confirmation } onChange={ ( e ) => onChange( { confirmation: e.target.value } ) } />
						</FormField>
					</TabsContent>
				</Tabs>
			</DialogContent>
		</Dialog>
	);
}
