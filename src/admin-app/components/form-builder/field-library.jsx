import { useMemo, useState } from 'react';
import { Plus } from 'lucide-react';
import { SearchInput } from '../ui/search-input';
import { cn } from '../../lib/utils';

/**
 * Left panel: the same grouped Field Library Field_Types::library() has
 * always provided (Basic/Choice/Date-Time/Files/Personal/Address/Talent/
 * Location/Utility) — search-filtered, drag-to-canvas (native HTML5 DnD,
 * same payload shape assets/admin/form-builder.js used:
 * `{source:'library', item}`) plus a keyboard/touch-friendly "+" button so
 * adding a field never requires a mouse drag.
 */
export function FieldLibrary( { library, onAdd } ) {
	const [ search, setSearch ] = useState( '' );

	const groups = useMemo( () => {
		const term = search.trim().toLowerCase();
		return Object.entries( library || {} )
			.map( ( [ key, group ] ) => ( {
				key,
				label: group.label,
				items: term ? group.items.filter( ( item ) => item.label.toLowerCase().includes( term ) ) : group.items,
			} ) )
			.filter( ( group ) => group.items.length > 0 );
	}, [ library, search ] );

	return (
		<div className="am-flex am-h-full am-flex-col am-gap-3 am-overflow-hidden am-p-3">
			<SearchInput value={ search } onChange={ setSearch } placeholder="Search fields…" />
			<div className="am-flex-1 am-overflow-y-auto am-pr-1">
				{ groups.length === 0 ? (
					<p className="am-py-6 am-text-center am-text-sm am-text-muted-foreground">No fields match “{ search }”.</p>
				) : (
					groups.map( ( group ) => (
						<div key={ group.key } className="am-mb-4">
							<h3 className="am-mb-1.5 am-px-1 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">{ group.label }</h3>
							<div className="am-flex am-flex-col am-gap-1">
								{ group.items.map( ( item, i ) => (
									<LibraryItem key={ group.key + i } item={ item } onAdd={ onAdd } />
								) ) }
							</div>
						</div>
					) )
				) }
			</div>
		</div>
	);
}

function LibraryItem( { item, onAdd } ) {
	return (
		<div
			role="button"
			tabIndex={ 0 }
			draggable
			onDragStart={ ( e ) => {
				e.dataTransfer.setData( 'application/json', JSON.stringify( { source: 'library', item } ) );
				e.dataTransfer.effectAllowed = 'copy';
			} }
			onKeyDown={ ( e ) => {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					onAdd( item );
				}
			} }
			aria-label={ `Add ${ item.label } field` }
			className={ cn(
				'am-group am-flex am-cursor-grab am-items-center am-gap-2 am-rounded-md am-border am-border-transparent am-px-2 am-py-1.5 am-text-sm am-text-foreground am-transition-colors',
				'hover:am-border-border hover:am-bg-accent focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring active:am-cursor-grabbing'
			) }
		>
			<span className={ cn( 'dashicons', item.icon || 'dashicons-forms', 'am-text-muted-foreground' ) } aria-hidden="true" />
			<span className="am-flex-1 am-truncate">{ item.label }</span>
			<button
				type="button"
				tabIndex={ -1 }
				onClick={ ( e ) => {
					e.stopPropagation();
					onAdd( item );
				} }
				aria-label={ `Add ${ item.label } field` }
				className="am-flex am-h-6 am-w-6 am-shrink-0 am-items-center am-justify-center am-rounded am-text-muted-foreground am-opacity-0 am-transition-opacity hover:am-bg-secondary hover:am-text-foreground group-hover:am-opacity-100 group-focus-within:am-opacity-100"
			>
				<Plus className="am-h-3.5 am-w-3.5" aria-hidden="true" />
			</button>
		</div>
	);
}
