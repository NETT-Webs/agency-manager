import * as DropdownPrimitive from '@radix-ui/react-dropdown-menu';
import { Check, ChevronRight, Circle } from 'lucide-react';
import { cn } from '../../lib/utils';

export const DropdownMenu = DropdownPrimitive.Root;
export const DropdownMenuTrigger = DropdownPrimitive.Trigger;
export const DropdownMenuGroup = DropdownPrimitive.Group;
export const DropdownMenuRadioGroup = DropdownPrimitive.RadioGroup;

export function DropdownMenuContent( { className, sideOffset = 6, ...props } ) {
	return (
		<DropdownPrimitive.Portal>
			<DropdownPrimitive.Content
				sideOffset={ sideOffset }
				className={ cn(
					'am-z-50 am-min-w-[10rem] am-overflow-hidden am-rounded-md am-border am-border-border am-bg-card am-p-1 am-text-foreground am-shadow-md',
					className
				) }
				{ ...props }
			/>
		</DropdownPrimitive.Portal>
	);
}

export function DropdownMenuItem( { className, inset, destructive, ...props } ) {
	return (
		<DropdownPrimitive.Item
			className={ cn(
				'am-relative am-flex am-cursor-pointer am-select-none am-items-center am-gap-2 am-rounded-sm am-px-2 am-py-1.5 am-text-sm am-outline-none am-transition-colors',
				'focus:am-bg-accent focus:am-text-accent-foreground data-[disabled]:am-pointer-events-none data-[disabled]:am-opacity-50',
				destructive && 'am-text-destructive focus:am-bg-destructive/10 focus:am-text-destructive',
				inset && 'am-pl-8',
				className
			) }
			{ ...props }
		/>
	);
}

export function DropdownMenuRadioItem( { className, children, ...props } ) {
	return (
		<DropdownPrimitive.RadioItem
			className={ cn(
				'am-relative am-flex am-cursor-pointer am-select-none am-items-center am-gap-2 am-rounded-sm am-py-1.5 am-pl-8 am-pr-2 am-text-sm am-outline-none am-transition-colors focus:am-bg-accent focus:am-text-accent-foreground',
				className
			) }
			{ ...props }
		>
			<span className="am-absolute am-left-2 am-flex am-h-3.5 am-w-3.5 am-items-center am-justify-center">
				<DropdownPrimitive.ItemIndicator>
					<Circle className="am-h-2 am-w-2 am-fill-current" aria-hidden="true" />
				</DropdownPrimitive.ItemIndicator>
			</span>
			{ children }
		</DropdownPrimitive.RadioItem>
	);
}

export function DropdownMenuLabel( { className, ...props } ) {
	return <div className={ cn( 'am-px-2 am-py-1.5 am-text-xs am-font-semibold am-text-muted-foreground', className ) } { ...props } />;
}

export function DropdownMenuSeparator( { className, ...props } ) {
	return <DropdownPrimitive.Separator className={ cn( 'am-my-1 am-h-px am-bg-border', className ) } { ...props } />;
}

export { Check as DropdownCheckIcon, ChevronRight as DropdownChevronIcon };
