import { Search, X } from 'lucide-react';
import { cn } from '../../lib/utils';

export function SearchInput( { value, onChange, placeholder = 'Search…', className } ) {
	return (
		<div className={ cn( 'am-relative am-w-full am-max-w-xs', className ) }>
			<Search className="am-pointer-events-none am-absolute am-left-2.5 am-top-1/2 am-h-4 am-w-4 -am-translate-y-1/2 am-text-muted-foreground" aria-hidden="true" />
			<input
				type="search"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
				aria-label={ placeholder }
				className="am-h-9 am-w-full am-rounded-md am-border am-border-input am-bg-background am-pl-8 am-pr-8 am-text-sm am-text-foreground am-shadow-sm placeholder:am-text-muted-foreground focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring"
			/>
			{ value && (
				<button
					type="button"
					onClick={ () => onChange( '' ) }
					aria-label="Clear search"
					className="am-absolute am-right-2 am-top-1/2 -am-translate-y-1/2 am-text-muted-foreground hover:am-text-foreground"
				>
					<X className="am-h-3.5 am-w-3.5" aria-hidden="true" />
				</button>
			) }
		</div>
	);
}
