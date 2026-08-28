import { cva } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const badgeVariants = cva(
	'am-inline-flex am-items-center am-rounded-full am-border am-px-2.5 am-py-0.5 am-text-xs am-font-medium am-transition-colors',
	{
		variants: {
			variant: {
				default: 'am-border-transparent am-bg-primary am-text-primary-foreground',
				secondary: 'am-border-transparent am-bg-secondary am-text-secondary-foreground',
				outline: 'am-border-border am-text-foreground',
				success: 'am-border-transparent am-bg-green-100 am-text-green-800',
				warning: 'am-border-transparent am-bg-amber-100 am-text-amber-800',
				destructive: 'am-border-transparent am-bg-destructive am-text-destructive-foreground',
			},
		},
		defaultVariants: { variant: 'default' },
	}
);

export function Badge( { className, variant, ...props } ) {
	return <span className={ cn( badgeVariants( { variant } ), className ) } { ...props } />;
}
