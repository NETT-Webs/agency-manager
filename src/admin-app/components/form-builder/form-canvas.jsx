import { useState } from 'react';
import { GripVertical, Copy, Trash2, ChevronUp, ChevronDown, FileQuestion } from 'lucide-react';
import { EmptyState } from '../ui/empty-state';
import { cn } from '../../lib/utils';

/**
 * Center panel — the form's field order, one row per field. Preserves the
 * original interaction model (drag to reorder/insert from the library,
 * click a row to edit its settings) with clearer visual feedback (drop-line
 * indicator, drag preview opacity) plus keyboard-operable Up/Down/Duplicate/
 * Delete controls so reordering never requires a mouse drag.
 */
export function FormCanvas( { fields, selectedId, typesMeta, onSelect, onAddAt, onMove, onReorder, onDuplicate, onDelete } ) {
	const [ dragOverIndex, setDragOverIndex ] = useState( null );
	const [ draggingId, setDraggingId ] = useState( null );

	function handleDragOver( e ) {
		e.preventDefault();
		e.dataTransfer.dropEffect = draggingId ? 'move' : 'copy';
		const rows = Array.from( e.currentTarget.querySelectorAll( '[data-canvas-row]' ) );
		let index = rows.length;
		for ( let i = 0; i < rows.length; i++ ) {
			const rect = rows[ i ].getBoundingClientRect();
			if ( e.clientY < rect.top + rect.height / 2 ) {
				index = i;
				break;
			}
		}
		setDragOverIndex( index );
	}

	function handleDrop( e ) {
		e.preventDefault();
		const raw = e.dataTransfer.getData( 'application/json' );
		const index = dragOverIndex === null ? fields.length : dragOverIndex;
		setDragOverIndex( null );
		setDraggingId( null );
		if ( ! raw ) {
			return;
		}
		let payload;
		try {
			payload = JSON.parse( raw );
		} catch ( err ) {
			return;
		}
		if ( 'library' === payload.source ) {
			onAddAt( payload.item, index );
		} else if ( 'canvas' === payload.source ) {
			onReorder( payload.id, index );
		}
	}

	return (
		<div className="am-flex am-h-full am-flex-col am-overflow-hidden am-p-3">
			<div
				className="am-flex-1 am-overflow-y-auto am-rounded-md"
				onDragOver={ handleDragOver }
				onDragLeave={ () => setDragOverIndex( null ) }
				onDrop={ handleDrop }
			>
				{ fields.length === 0 ? (
					<EmptyState
						icon={ FileQuestion }
						title="No fields yet"
						description="Drag a field from the library on the left, or use its “+” button, to start building this form."
					/>
				) : (
					<div className="am-flex am-flex-col am-gap-1">
						{ fields.map( ( field, index ) => (
							<div key={ field.id }>
								{ dragOverIndex === index && <DropIndicator /> }
								<CanvasRow
									field={ field }
									index={ index }
									total={ fields.length }
									selected={ field.id === selectedId }
									typeLabel={ ( typesMeta[ field.type ] || {} ).label || field.type }
									dragging={ draggingId === field.id }
									onDragStart={ () => setDraggingId( field.id ) }
									onDragEnd={ () => { setDraggingId( null ); setDragOverIndex( null ); } }
									onSelect={ () => onSelect( field.id ) }
									onMoveUp={ () => onMove( field.id, -1 ) }
									onMoveDown={ () => onMove( field.id, 1 ) }
									onDuplicate={ () => onDuplicate( field.id ) }
									onDelete={ () => onDelete( field.id ) }
								/>
							</div>
						) ) }
						{ dragOverIndex === fields.length && <DropIndicator /> }
					</div>
				) }
			</div>
		</div>
	);
}

function DropIndicator() {
	return <div className="am-my-0.5 am-h-0.5 am-rounded-full am-bg-primary" />;
}

function CanvasRow( { field, index, total, selected, typeLabel, dragging, onDragStart, onDragEnd, onSelect, onMoveUp, onMoveDown, onDuplicate, onDelete } ) {
	return (
		<div
			data-canvas-row
			role="button"
			tabIndex={ 0 }
			draggable
			onDragStart={ ( e ) => {
				onDragStart();
				e.dataTransfer.setData( 'application/json', JSON.stringify( { source: 'canvas', id: field.id } ) );
				e.dataTransfer.effectAllowed = 'move';
			} }
			onDragEnd={ onDragEnd }
			onClick={ onSelect }
			onKeyDown={ ( e ) => {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					onSelect();
				}
			} }
			aria-label={ `Edit field: ${ field.label || field.type }` }
			aria-pressed={ selected }
			className={ cn(
				'am-group am-flex am-cursor-pointer am-items-center am-gap-2 am-rounded-md am-border am-bg-card am-p-2.5 am-transition-colors focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring',
				selected ? 'am-border-primary am-ring-1 am-ring-primary' : 'am-border-border hover:am-border-input hover:am-bg-accent/50',
				dragging && 'am-opacity-40'
			) }
		>
			<GripVertical className="am-h-4 am-w-4 am-shrink-0 am-cursor-grab am-text-muted-foreground" aria-hidden="true" />

			<div className="am-min-w-0 am-flex-1">
				<div className="am-flex am-items-center am-gap-1 am-text-sm am-font-medium am-text-foreground">
					<span className="am-truncate">{ field.label || '(untitled field)' }</span>
					{ field.required && <span className="am-text-destructive" aria-label="Required">*</span> }
				</div>
				<div className="am-truncate am-text-xs am-text-muted-foreground">{ typeLabel } &middot; { field.key }</div>
			</div>

			<div className="am-flex am-shrink-0 am-items-center am-gap-0.5 am-opacity-0 am-transition-opacity group-hover:am-opacity-100 group-focus-within:am-opacity-100">
				<RowButton label="Move up" disabled={ index === 0 } onClick={ onMoveUp }><ChevronUp className="am-h-3.5 am-w-3.5" aria-hidden="true" /></RowButton>
				<RowButton label="Move down" disabled={ index === total - 1 } onClick={ onMoveDown }><ChevronDown className="am-h-3.5 am-w-3.5" aria-hidden="true" /></RowButton>
				<RowButton label="Duplicate field" onClick={ onDuplicate }><Copy className="am-h-3.5 am-w-3.5" aria-hidden="true" /></RowButton>
				<RowButton label="Delete field" destructive onClick={ onDelete }><Trash2 className="am-h-3.5 am-w-3.5" aria-hidden="true" /></RowButton>
			</div>
		</div>
	);
}

function RowButton( { label, destructive, disabled, onClick, children } ) {
	return (
		<button
			type="button"
			aria-label={ label }
			title={ label }
			disabled={ disabled }
			onClick={ ( e ) => { e.stopPropagation(); onClick(); } }
			className={ cn(
				'am-flex am-h-6 am-w-6 am-items-center am-justify-center am-rounded am-text-muted-foreground am-transition-colors disabled:am-pointer-events-none disabled:am-opacity-30',
				destructive ? 'hover:am-bg-destructive/10 hover:am-text-destructive' : 'hover:am-bg-secondary hover:am-text-foreground'
			) }
		>
			{ children }
		</button>
	);
}
