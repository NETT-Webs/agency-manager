import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { ImagePlus, X } from 'lucide-react';
import { Button } from './ui/button';

/**
 * Wraps WordPress's own core media modal (`wp.media`, enqueued via
 * `wp_enqueue_media()` — see Admin_App_Page) instead of reimplementing an
 * upload/library UI. Value is a plain array of attachment IDs, matching the
 * `image_ids` shape Settings/Website_Display_Page already stores.
 */
export function ImagePicker( { value, onChange, multiple = true, label = 'Select Images' } ) {
	const [ items, setItems ] = useState( [] );

	useEffect( () => {
		if ( ! value || value.length === 0 ) {
			setItems( [] );
			return;
		}
		Promise.all(
			value.map( ( id ) => apiFetch( { path: `/wp/v2/media/${ id }?_fields=id,source_url` } ).catch( () => null ) )
		).then( ( results ) => setItems( results.filter( Boolean ) ) );
	}, [ value ] );

	function openPicker() {
		if ( ! window.wp?.media ) {
			return;
		}
		const frame = window.wp.media( { title: label, multiple, library: { type: 'image' } } );
		frame.on( 'select', () => {
			const selection = frame.state().get( 'selection' ).toArray().map( ( a ) => a.toJSON() );
			const ids = selection.map( ( a ) => a.id );
			onChange( multiple ? [ ...value, ...ids ] : ids );
		} );
		frame.open();
	}

	function removeAt( id ) {
		onChange( value.filter( ( v ) => v !== id ) );
	}

	return (
		<div>
			<div className="am-mb-2 am-flex am-flex-wrap am-gap-2">
				{ items.map( ( item ) => (
					<div key={ item.id } className="am-group am-relative am-h-16 am-w-16 am-overflow-hidden am-rounded-md am-border am-border-border">
						<img src={ item.source_url } alt="" className="am-h-full am-w-full am-object-cover" />
						<button
							type="button"
							onClick={ () => removeAt( item.id ) }
							aria-label="Remove image"
							className="am-absolute am-right-0.5 am-top-0.5 am-rounded am-bg-foreground/70 am-p-0.5 am-text-white"
						>
							<X className="am-h-3 am-w-3" aria-hidden="true" />
						</button>
					</div>
				) ) }
			</div>
			<div className="am-flex am-gap-2">
				<Button type="button" size="sm" variant="outline" onClick={ openPicker }>
					<ImagePlus className="am-h-3.5 am-w-3.5" aria-hidden="true" /> { label }
				</Button>
				{ value.length > 0 && (
					<Button type="button" size="sm" variant="ghost" onClick={ () => onChange( [] ) }>Clear</Button>
				) }
			</div>
		</div>
	);
}
