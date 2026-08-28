import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from '../../lib/utils';

export const Dialog = DialogPrimitive.Root;
export const DialogTrigger = DialogPrimitive.Trigger;
export const DialogClose = DialogPrimitive.Close;

export function DialogContent( { className, children, showClose = true, ...props } ) {
	return (
		<DialogPrimitive.Portal>
			<DialogPrimitive.Overlay className="am-overlay-anim am-fixed am-inset-0 am-z-50 am-bg-foreground/40" />
			<DialogPrimitive.Content
				className={ cn(
					'am-dialog-anim am-fixed am-left-1/2 am-top-1/2 am-z-50 am-w-full am-max-w-lg -am-translate-x-1/2 -am-translate-y-1/2 am-rounded-lg am-border am-border-border am-bg-card am-p-6 am-shadow-lg am-focus:outline-none',
					className
				) }
				{ ...props }
			>
				{ children }
				{ showClose && (
					<DialogPrimitive.Close className="am-absolute am-right-4 am-top-4 am-rounded-sm am-text-muted-foreground am-transition-colors hover:am-text-foreground focus-visible:am-outline-none">
						<X className="am-h-4 am-w-4" aria-hidden="true" />
						<span className="am-sr-only">Close</span>
					</DialogPrimitive.Close>
				) }
			</DialogPrimitive.Content>
		</DialogPrimitive.Portal>
	);
}

export function DialogHeader( { className, ...props } ) {
	return <div className={ cn( 'am-mb-4 am-flex am-flex-col am-gap-1', className ) } { ...props } />;
}

export function DialogTitle( { className, ...props } ) {
	return <DialogPrimitive.Title className={ cn( 'am-text-base am-font-semibold am-text-foreground', className ) } { ...props } />;
}

export function DialogDescription( { className, ...props } ) {
	return <DialogPrimitive.Description className={ cn( 'am-text-sm am-text-muted-foreground', className ) } { ...props } />;
}

export function DialogFooter( { className, ...props } ) {
	return <div className={ cn( 'am-mt-6 am-flex am-justify-end am-gap-2', className ) } { ...props } />;
}
