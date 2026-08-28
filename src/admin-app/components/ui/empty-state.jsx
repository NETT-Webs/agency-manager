import { cn } from '../../lib/utils';

/**
 * The shared empty-state pattern used across every list-style panel
 * ("No talent added yet" etc.) — one component so the wording/spacing stays
 * consistent everywhere it's used, now and in later phases.
 */
export function EmptyState( { icon: Icon, title, description, action, className } ) {
	return (
		<div className={ cn( 'am-flex am-flex-col am-items-center am-justify-center am-gap-2 am-rounded-lg am-border am-border-dashed am-border-border am-p-10 am-text-center', className ) }>
			{ Icon && <Icon className="am-h-8 am-w-8 am-text-muted-foreground" aria-hidden="true" /> }
			<p className="am-text-sm am-font-medium am-text-foreground">{ title }</p>
			{ description && <p className="am-max-w-sm am-text-sm am-text-muted-foreground">{ description }</p> }
			{ action && <div className="am-mt-2">{ action }</div> }
		</div>
	);
}
