import { ImagePlus, X } from 'lucide-react';
import { Button } from '../ui/button';

/**
 * Featured image (post thumbnail) picker — wraps the same core wp.media
 * modal as the Gallery Manager and the Website Display screen's Scouting
 * Images picker, just constrained to a single selection.
 */
export function FeaturedImagePicker( { id, url, onChange } ) {
	function openPicker() {
		if ( ! window.wp?.media ) {
			return;
		}
		const frame = window.wp.media( { title: 'Select Featured Image', multiple: false, library: { type: 'image' } } );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			onChange( attachment.id, attachment.url );
		} );
		frame.open();
	}

	return (
		<div className="am-flex am-flex-col am-gap-3">
			<div className="am-flex am-h-40 am-w-full am-items-center am-justify-center am-overflow-hidden am-rounded-md am-border am-border-dashed am-border-border am-bg-secondary/40">
				{ url ? (
					<img src={ url } alt="" className="am-h-full am-w-full am-object-cover" />
				) : (
					<span className="am-text-xs am-text-muted-foreground">No image selected</span>
				) }
			</div>
			<div className="am-flex am-gap-2">
				<Button type="button" size="sm" variant="outline" onClick={ openPicker }>
					<ImagePlus className="am-h-3.5 am-w-3.5" aria-hidden="true" /> { id ? 'Change Image' : 'Select Image' }
				</Button>
				{ id > 0 && (
					<Button type="button" size="sm" variant="ghost" onClick={ () => onChange( 0, '' ) }>
						<X className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Remove
					</Button>
				) }
			</div>
		</div>
	);
}
