import { cn } from '../../lib/utils';

export function Card( { className, ...props } ) {
	return <div className={ cn( 'am-rounded-lg am-border am-border-border am-bg-card am-text-card-foreground am-shadow-sm', className ) } { ...props } />;
}

export function CardHeader( { className, ...props } ) {
	return <div className={ cn( 'am-flex am-flex-col am-space-y-1 am-p-4', className ) } { ...props } />;
}

export function CardTitle( { className, ...props } ) {
	return <h3 className={ cn( 'am-text-sm am-font-medium am-text-muted-foreground', className ) } { ...props } />;
}

export function CardContent( { className, ...props } ) {
	return <div className={ cn( 'am-p-4 am-pt-0', className ) } { ...props } />;
}
