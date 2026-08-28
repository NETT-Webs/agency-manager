import { Checkbox } from '../ui/form-controls';
import { EmptyState } from '../ui/empty-state';

/** Multi-select taxonomy terms as a checkbox list — terms themselves are managed on their own admin screen (see the sidebar's Categories/Groups links), this only assigns existing ones. */
export function TermChecklist( { terms, selected, onChange, manageUrl, manageLabel } ) {
	function toggle( id ) {
		onChange( selected.includes( id ) ? selected.filter( ( t ) => t !== id ) : [ ...selected, id ] );
	}

	if ( terms.length === 0 ) {
		return (
			<EmptyState
				title="No options yet"
				description={ manageLabel ? `Add some from ${ manageLabel } first.` : 'None have been created yet.' }
				action={ manageUrl && <a href={ manageUrl } className="am-text-sm am-text-primary hover:am-underline">{ manageLabel }</a> }
				className="am-border-none am-p-4"
			/>
		);
	}

	return (
		<div className="am-flex am-flex-wrap am-gap-x-4 am-gap-y-2">
			{ terms.map( ( term ) => (
				<label key={ term.id } className="am-flex am-items-center am-gap-2 am-text-sm">
					<Checkbox checked={ selected.includes( term.id ) } onChange={ () => toggle( term.id ) } />
					{ term.name }
				</label>
			) ) }
		</div>
	);
}
