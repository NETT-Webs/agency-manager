import { cva } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const buttonVariants = cva(
	'am-inline-flex am-items-center am-justify-center am-gap-2 am-whitespace-nowrap am-rounded-md am-text-sm am-font-medium am-transition-colors focus-visible:am-outline-none focus-visible:am-ring-2 focus-visible:am-ring-ring focus-visible:am-ring-offset-2 disabled:am-pointer-events-none disabled:am-opacity-50',
	{
		variants: {
			variant: {
				default: 'am-bg-primary am-text-primary-foreground hover:am-bg-primary/90',
				secondary: 'am-bg-secondary am-text-secondary-foreground hover:am-bg-secondary/80',
				outline: 'am-border am-border-input am-bg-background hover:am-bg-accent hover:am-text-accent-foreground',
				ghost: 'hover:am-bg-accent hover:am-text-accent-foreground',
				destructive: 'am-bg-destructive am-text-destructive-foreground hover:am-bg-destructive/90',
			},
			size: {
				default: 'am-h-9 am-px-4 am-py-2',
				sm: 'am-h-8 am-px-3 am-text-xs',
				lg: 'am-h-10 am-px-6',
				icon: 'am-h-9 am-w-9',
			},
		},
		defaultVariants: { variant: 'default', size: 'default' },
	}
);

export function Button( { className, variant, size, as: Tag = 'button', ...props } ) {
	return <Tag className={ cn( buttonVariants( { variant, size } ), className ) } { ...props } />;
}
