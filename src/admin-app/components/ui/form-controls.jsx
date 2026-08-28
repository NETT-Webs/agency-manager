import { cn } from '../../lib/utils';

export function Label( { className, ...props } ) {
	return <label className={ cn( 'am-mb-1.5 am-block am-text-sm am-font-medium am-text-foreground', className ) } { ...props } />;
}

export function Input( { className, ...props } ) {
	return (
		<input
			className={ cn(
				'am-flex am-h-9 am-w-full am-rounded-md am-border am-border-input am-bg-background am-px-3 am-py-1 am-text-sm am-text-foreground am-shadow-sm am-transition-colors',
				'placeholder:am-text-muted-foreground focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring disabled:am-cursor-not-allowed disabled:am-opacity-50',
				className
			) }
			{ ...props }
		/>
	);
}

export function Textarea( { className, ...props } ) {
	return (
		<textarea
			className={ cn(
				'am-flex am-min-h-[80px] am-w-full am-rounded-md am-border am-border-input am-bg-background am-px-3 am-py-2 am-text-sm am-text-foreground am-shadow-sm am-transition-colors',
				'placeholder:am-text-muted-foreground focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring disabled:am-cursor-not-allowed disabled:am-opacity-50',
				className
			) }
			{ ...props }
		/>
	);
}

export function Checkbox( { className, ...props } ) {
	return (
		<input
			type="checkbox"
			className={ cn(
				'am-h-4 am-w-4 am-rounded am-border am-border-input am-text-primary am-shadow-sm focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring',
				className
			) }
			{ ...props }
		/>
	);
}

export function FormField( { label, htmlFor, description, className, children } ) {
	return (
		<div className={ cn( 'am-mb-4', className ) }>
			{ label && <Label htmlFor={ htmlFor }>{ label }</Label> }
			{ children }
			{ description && <p className="am-mt-1 am-text-xs am-text-muted-foreground">{ description }</p> }
		</div>
	);
}
