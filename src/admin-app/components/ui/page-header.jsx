import { ChevronRight } from 'lucide-react';
import { cn } from '../../lib/utils';

/** Breadcrumb trail: [{label, href?}] — last item (no href) renders as plain text (current page). */
export function Breadcrumbs( { items } ) {
	if ( ! items || items.length < 2 ) {
		return null;
	}
	return (
		<nav aria-label="Breadcrumb" className="am-mb-1 am-flex am-items-center am-gap-1 am-text-xs am-text-muted-foreground">
			{ items.map( ( item, i ) => (
				<span key={ i } className="am-flex am-items-center am-gap-1">
					{ i > 0 && <ChevronRight className="am-h-3 am-w-3" aria-hidden="true" /> }
					{ item.href ? (
						<a href={ item.href } className="hover:am-text-foreground hover:am-underline">{ item.label }</a>
					) : (
						<span className="am-text-foreground/70">{ item.label }</span>
					) }
				</span>
			) ) }
		</nav>
	);
}

/** Standard page header: optional breadcrumbs, title, description, right-aligned actions. Used on every migrated screen. */
export function PageHeader( { title, description, breadcrumbs, actions, className } ) {
	return (
		<div className={ cn( 'am-mb-6 am-flex am-flex-col am-gap-4 sm:am-flex-row sm:am-items-start sm:am-justify-between', className ) }>
			<div className="am-min-w-0">
				<Breadcrumbs items={ breadcrumbs } />
				<h2 className="am-text-xl am-font-semibold am-tracking-tight am-text-foreground">{ title }</h2>
				{ description && <p className="am-mt-1 am-max-w-2xl am-text-sm am-text-muted-foreground">{ description }</p> }
			</div>
			{ actions && <div className="am-flex am-shrink-0 am-items-center am-gap-2">{ actions }</div> }
		</div>
	);
}
