import * as SelectPrimitive from '@radix-ui/react-select';
import { Check, ChevronDown } from 'lucide-react';
import { cn } from '../../lib/utils';

export const Select = SelectPrimitive.Root;
export const SelectGroup = SelectPrimitive.Group;
export const SelectValue = SelectPrimitive.Value;

export function SelectTrigger( { className, children, ...props } ) {
	return (
		<SelectPrimitive.Trigger
			className={ cn(
				'am-flex am-h-9 am-w-full am-items-center am-justify-between am-gap-2 am-rounded-md am-border am-border-input am-bg-background am-px-3 am-py-2 am-text-sm am-text-foreground',
				'focus:am-outline-none focus:am-ring-2 focus:am-ring-ring disabled:am-cursor-not-allowed disabled:am-opacity-50',
				className
			) }
			{ ...props }
		>
			{ children }
			<SelectPrimitive.Icon>
				<ChevronDown className="am-h-4 am-w-4 am-opacity-50" aria-hidden="true" />
			</SelectPrimitive.Icon>
		</SelectPrimitive.Trigger>
	);
}

export function SelectContent( { className, children, ...props } ) {
	return (
		<SelectPrimitive.Portal>
			<SelectPrimitive.Content
				className={ cn( 'am-z-50 am-overflow-hidden am-rounded-md am-border am-border-border am-bg-card am-text-foreground am-shadow-md', className ) }
				position="popper"
				sideOffset={ 4 }
				{ ...props }
			>
				<SelectPrimitive.Viewport className="am-p-1">{ children }</SelectPrimitive.Viewport>
			</SelectPrimitive.Content>
		</SelectPrimitive.Portal>
	);
}

export function SelectItem( { className, children, ...props } ) {
	return (
		<SelectPrimitive.Item
			className={ cn(
				'am-relative am-flex am-cursor-pointer am-select-none am-items-center am-rounded-sm am-py-1.5 am-pl-8 am-pr-2 am-text-sm am-outline-none',
				'focus:am-bg-accent focus:am-text-accent-foreground data-[disabled]:am-pointer-events-none data-[disabled]:am-opacity-50',
				className
			) }
			{ ...props }
		>
			<span className="am-absolute am-left-2 am-flex am-h-3.5 am-w-3.5 am-items-center am-justify-center">
				<SelectPrimitive.ItemIndicator>
					<Check className="am-h-3.5 am-w-3.5" aria-hidden="true" />
				</SelectPrimitive.ItemIndicator>
			</span>
			<SelectPrimitive.ItemText>{ children }</SelectPrimitive.ItemText>
		</SelectPrimitive.Item>
	);
}
