import { cn } from '../../lib/utils';

export function Progress( { value, className } ) {
	const pct = Math.max( 0, Math.min( 100, value ) );
	return (
		<div className={ cn( 'am-h-2 am-w-full am-overflow-hidden am-rounded-full am-bg-secondary', className ) } role="progressbar" aria-valuenow={ pct } aria-valuemin={ 0 } aria-valuemax={ 100 }>
			<div className="am-h-full am-rounded-full am-bg-primary am-transition-[width]" style={ { width: `${ pct }%` } } />
		</div>
	);
}
