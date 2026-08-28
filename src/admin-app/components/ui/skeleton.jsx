import { cn } from '../../lib/utils';

export function Skeleton( { className, ...props } ) {
	return <div className={ cn( 'am-animate-pulse am-rounded-md am-bg-muted', className ) } { ...props } />;
}
