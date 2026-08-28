import { cn } from '../../lib/utils';

export function Table( { className, ...props } ) {
	return (
		<div className="am-w-full am-overflow-x-auto am-rounded-lg am-border am-border-border">
			<table className={ cn( 'am-w-full am-caption-bottom am-text-sm', className ) } { ...props } />
		</div>
	);
}

export function TableHeader( { className, ...props } ) {
	return <thead className={ cn( 'am-bg-secondary/60', className ) } { ...props } />;
}

export function TableBody( { className, ...props } ) {
	return <tbody className={ cn( 'am-divide-y am-divide-border', className ) } { ...props } />;
}

export function TableRow( { className, ...props } ) {
	return <tr className={ cn( 'am-transition-colors hover:am-bg-accent/40', className ) } { ...props } />;
}

export function TableHead( { className, ...props } ) {
	return (
		<th
			className={ cn( 'am-h-10 am-whitespace-nowrap am-px-4 am-text-left am-align-middle am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground', className ) }
			{ ...props }
		/>
	);
}

export function TableCell( { className, ...props } ) {
	return <td className={ cn( 'am-px-4 am-py-3 am-align-middle', className ) } { ...props } />;
}
