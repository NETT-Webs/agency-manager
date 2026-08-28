import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { ImagePlus, X, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '../ui/button';

/**
 * Ordered gallery of attachment IDs — order matters (the first image is
 * used as the card's cover image, see Frontend\Card_Renderer::get_card_image_id())
 * so reordering is a real, backend-supported operation, not cosmetic.
 * Stored the same way Cpt\Meta_Boxes/Field_Mapper already do: a
 * comma-separated list of attachment IDs under `_am_gallery_ids`.
 */
export function GalleryManager( { ids, onChange } ) {
	const [ items, setItems ] = useState( [] );

	useEffect( () => {
		if ( ! ids || ids.length === 0 ) {
			setItems( [] );
			return;
		}
		Promise.all(
			ids.map( ( id ) => apiFetch( { path: `/wp/v2/media/${ id }?_fields=id,source_url` } ).catch( () => ( { id, source_url: '' } ) ) )
		).then( setItems );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ ids.join( ',' ) ] );

	function openPicker() {
		if ( ! window.wp?.media ) {
			return;
		}
		const frame = window.wp.media( { title: 'Add Gallery Images', multiple: true, library: { type: 'image' } } );
		frame.on( 'select', () => {
			const selected = frame.state().get( 'selection' ).toArray().map( ( a ) => a.id );
			onChange( [ ...ids, ...selected.filter( ( id ) => ! ids.includes( id ) ) ] );
		} );
		frame.open();
	}

	function removeAt( index ) {
		onChange( ids.filter( ( _, i ) => i !== index ) );
	}

	function move( index, direction ) {
		const next = [ ...ids ];
		const target = index + direction;
		if ( target < 0 || target >= next.length ) {
			return;
		}
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
	}

	return (
		<div>
			{ items.length > 0 && (
				<div className="am-mb-3 am-grid am-grid-cols-3 am-gap-2 sm:am-grid-cols-4">
					{ items.map( ( item, i ) => (
						<div key={ item.id } className="am-group am-relative am-aspect-square am-overflow-hidden am-rounded-md am-border am-border-border">
							{ item.source_url && <img src={ item.source_url } alt="" className="am-h-full am-w-full am-object-cover" /> }
							{ 0 === i && (
								<span className="am-absolute am-left-1 am-top-1 am-rounded am-bg-primary am-px-1.5 am-py-0.5 am-text-[10px] am-font-medium am-text-primary-foreground">Cover</span>
							) }
							<div className="am-absolute am-inset-x-0 am-bottom-0 am-flex am-items-center am-justify-between am-bg-foreground/70 am-px-1 am-py-0.5 am-opacity-0 am-transition-opacity group-hover:am-opacity-100">
								<button type="button" onClick={ () => move( i, -1 ) } disabled={ 0 === i } aria-label="Move earlier" className="am-text-white disabled:am-opacity-30">
									<ChevronLeft className="am-h-3.5 am-w-3.5" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => removeAt( i ) } aria-label="Remove image" className="am-text-white">
									<X className="am-h-3.5 am-w-3.5" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => move( i, 1 ) } disabled={ i === items.length - 1 } aria-label="Move later" className="am-text-white disabled:am-opacity-30">
									<ChevronRight className="am-h-3.5 am-w-3.5" aria-hidden="true" />
								</button>
							</div>
						</div>
					) ) }
				</div>
			) }
			<Button type="button" size="sm" variant="outline" onClick={ openPicker }>
				<ImagePlus className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Add Gallery Images
			</Button>
		</div>
	);
}
