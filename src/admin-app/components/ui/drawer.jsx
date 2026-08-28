import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from '../../lib/utils';

/**
 * Right-hand side panel (e.g. field settings, submission detail) — same
 * Radix Dialog primitive as `dialog.jsx`, different geometry/animation.
 */
export const Drawer = DialogPrimitive.Root;
export const DrawerTrigger = DialogPrimitive.Trigger;
export const DrawerClose = DialogPrimitive.Close;

export function DrawerContent( { className, children, ...props } ) {
	return (
		<DialogPrimitive.Portal>
			<DialogPrimitive.Overlay className="am-overlay-anim am-fixed am-inset-0 am-z-50 am-bg-foreground/40" />
			<DialogPrimitive.Content
				className={ cn(
					'am-fixed am-right-0 am-top-0 am-z-50 am-flex am-h-full am-w-full am-max-w-md am-flex-col am-border-l am-border-border am-bg-card am-shadow-lg am-focus:outline-none',
					className
				) }
				{ ...props }
			>
				<DialogPrimitive.Close className="am-absolute am-right-4 am-top-4 am-rounded-sm am-text-muted-foreground am-transition-colors hover:am-text-foreground focus-visible:am-outline-none">
					<X className="am-h-4 am-w-4" aria-hidden="true" />
					<span className="am-sr-only">Close</span>
				</DialogPrimitive.Close>
				{ children }
			</DialogPrimitive.Content>
		</DialogPrimitive.Portal>
	);
}

export function DrawerHeader( { className, ...props } ) {
	return <div className={ cn( 'am-border-b am-border-border am-p-4', className ) } { ...props } />;
}

export function DrawerTitle( { className, ...props } ) {
	return <DialogPrimitive.Title className={ cn( 'am-pr-6 am-text-base am-font-semibold am-text-foreground', className ) } { ...props } />;
}

export function DrawerBody( { className, ...props } ) {
	return <div className={ cn( 'am-flex-1 am-overflow-y-auto am-p-4', className ) } { ...props } />;
}

export function DrawerFooter( { className, ...props } ) {
	return <div className={ cn( 'am-flex am-justify-end am-gap-2 am-border-t am-border-border am-p-4', className ) } { ...props } />;
}
