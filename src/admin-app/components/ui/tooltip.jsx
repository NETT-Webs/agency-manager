import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import { cn } from '../../lib/utils';

export const TooltipProvider = TooltipPrimitive.Provider;
export const Tooltip = TooltipPrimitive.Root;
export const TooltipTrigger = TooltipPrimitive.Trigger;

export function TooltipContent( { className, sideOffset = 6, ...props } ) {
	return (
		<TooltipPrimitive.Portal>
			<TooltipPrimitive.Content
				sideOffset={ sideOffset }
				className={ cn(
					'am-z-50 am-overflow-hidden am-rounded-md am-border am-border-border am-bg-card am-px-3 am-py-1.5 am-text-xs am-text-foreground am-shadow-md',
					className
				) }
				{ ...props }
			/>
		</TooltipPrimitive.Portal>
	);
}
