import { useEffect, useState } from 'react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { Button } from '../components/ui/button';
import { Skeleton } from '../components/ui/skeleton';
import { Input, Textarea, FormField } from '../components/ui/form-controls';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../components/ui/select';
import { ImagePicker } from '../components/image-picker';
import { useToast } from '../components/ui/toast';
import { getDisplaySettings, saveDisplaySettings } from '../lib/api';

const MODE_OPTIONS = [
	{ value: 'hidden', label: 'Hidden' },
	{ value: 'scouting', label: 'Now Scouting' },
	{ value: 'live', label: 'Live' },
];

function TypeSection( { type, state, setField } ) {
	const d = state.display[ type ];
	const ph = state.placeholder[ type ];
	const hp = state.homepage[ type ];

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<Card>
				<CardHeader><CardTitle>Display Mode</CardTitle></CardHeader>
				<CardContent>
					<div className="am-flex am-flex-wrap am-gap-4">
						{ MODE_OPTIONS.map( ( opt ) => (
							<label key={ opt.value } className="am-flex am-items-center am-gap-2 am-text-sm">
								<input
									type="radio"
									checked={ d === opt.value }
									onChange={ () => setField( `display.${ type }`, opt.value ) }
									className="am-h-4 am-w-4 am-text-primary"
								/>
								{ opt.label }
							</label>
						) ) }
					</div>
				</CardContent>
			</Card>

			<Card>
				<CardHeader><CardTitle>Placeholder Manager ("Now Scouting" cards)</CardTitle></CardHeader>
				<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
					<FormField label="Badge"><Input value={ ph.badge } onChange={ ( e ) => setField( `placeholder.${ type }.badge`, e.target.value ) } /></FormField>
					<FormField label="Title"><Input value={ ph.heading } onChange={ ( e ) => setField( `placeholder.${ type }.heading`, e.target.value ) } /></FormField>
					<FormField label="Description" className="sm:am-col-span-2">
						<Textarea rows={ 3 } value={ ph.description } onChange={ ( e ) => setField( `placeholder.${ type }.description`, e.target.value ) } />
					</FormField>
					<FormField label="Button Text"><Input value={ ph.button_text } onChange={ ( e ) => setField( `placeholder.${ type }.button_text`, e.target.value ) } /></FormField>
					<FormField label="Button Link"><Input type="url" value={ ph.button_link } onChange={ ( e ) => setField( `placeholder.${ type }.button_link`, e.target.value ) } /></FormField>
					<FormField label="Number of Placeholder Cards">
						<Input type="number" min={ 1 } max={ 24 } value={ ph.count } onChange={ ( e ) => setField( `placeholder.${ type }.count`, Number( e.target.value ) ) } />
					</FormField>
					<FormField label="Scouting Images" className="sm:am-col-span-2" description="Each placeholder card uses the next image in order, cycling once every image has been used. Leave empty to use the plain placeholder block.">
						<ImagePicker value={ ph.image_ids } onChange={ ( ids ) => setField( `placeholder.${ type }.image_ids`, ids ) } />
					</FormField>
				</CardContent>
			</Card>

			<Card>
				<CardHeader><CardTitle>Homepage Section</CardTitle></CardHeader>
				<CardContent className="am-grid am-grid-cols-1 am-gap-4 sm:am-grid-cols-2">
					<FormField label="Heading"><Input value={ hp.heading } onChange={ ( e ) => setField( `homepage.${ type }.heading`, e.target.value ) } /></FormField>
					<FormField label="Subheading"><Input value={ hp.subheading } onChange={ ( e ) => setField( `homepage.${ type }.subheading`, e.target.value ) } /></FormField>
					<FormField label="Button Text"><Input value={ hp.button_text } onChange={ ( e ) => setField( `homepage.${ type }.button_text`, e.target.value ) } /></FormField>
					<FormField label="Button Link"><Input type="url" value={ hp.button_link } onChange={ ( e ) => setField( `homepage.${ type }.button_link`, e.target.value ) } /></FormField>
					<FormField label="Number of Cards">
						<Input type="number" min={ 1 } max={ 24 } value={ hp.count } onChange={ ( e ) => setField( `homepage.${ type }.count`, Number( e.target.value ) ) } />
					</FormField>
					<FormField label="Display Mode">
						<Select value={ hp.display_mode } onValueChange={ ( v ) => setField( `homepage.${ type }.display_mode`, v ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="inherit">Inherit from Display Mode above</SelectItem>
								{ MODE_OPTIONS.map( ( opt ) => <SelectItem key={ opt.value } value={ opt.value }>{ opt.label }</SelectItem> ) }
							</SelectContent>
						</Select>
					</FormField>
					<FormField label="Card Hover Animation">
						<Select value={ hp.animation } onValueChange={ ( v ) => setField( `homepage.${ type }.animation`, v ) }>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="none">None</SelectItem>
								<SelectItem value="lift">Lift</SelectItem>
								<SelectItem value="zoom">Zoom</SelectItem>
								<SelectItem value="fade">Fade</SelectItem>
							</SelectContent>
						</Select>
					</FormField>
				</CardContent>
			</Card>
		</div>
	);
}

export function Display() {
	const toast = useToast();
	const [ state, setState ] = useState( null );
	const [ tab, setTab ] = useState( 'talent' );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		getDisplaySettings().then( setState ).catch( () => toast( 'Could not load Website Display settings.', 'error' ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	function setField( path, value ) {
		setState( ( prev ) => {
			const next = structuredClone( prev );
			const parts = path.split( '.' );
			let node = next;
			for ( let i = 0; i < parts.length - 1; i++ ) {
				node = node[ parts[ i ] ];
			}
			node[ parts[ parts.length - 1 ] ] = value;
			return next;
		} );
	}

	function save() {
		setSaving( true );
		saveDisplaySettings( state )
			.then( () => toast( 'Website Display settings saved.' ) )
			.catch( () => toast( 'Could not save settings.', 'error' ) )
			.finally( () => setSaving( false ) );
	}

	if ( ! state ) {
		return (
			<div className="am-flex am-flex-col am-gap-4">
				<Skeleton className="am-h-8 am-w-64" />
				<Skeleton className="am-h-64 am-w-full" />
			</div>
		);
	}

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="Website Display"
				description="Everything that controls how Talent and Locations appear on your website — Display Mode, the “Now Scouting” placeholders, and the homepage sections."
				actions={ <Button size="sm" onClick={ save } disabled={ saving }>{ saving ? 'Saving…' : 'Save Changes' }</Button> }
			/>

			<Tabs value={ tab } onValueChange={ setTab }>
				<TabsList>
					<TabsTrigger value="talent">Talent</TabsTrigger>
					<TabsTrigger value="location">Locations</TabsTrigger>
				</TabsList>
				<TabsContent value="talent"><TypeSection type="talent" state={ state } setField={ setField } /></TabsContent>
				<TabsContent value="location"><TypeSection type="location" state={ state } setField={ setField } /></TabsContent>
			</Tabs>
		</div>
	);
}
