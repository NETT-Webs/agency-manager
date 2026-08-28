import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose } from './dialog';
import { Button } from './button';

/**
 * Controlled confirm dialog for any destructive/irreversible admin action
 * (delete, archive, publish, reset). One component so the wording/spacing
 * stays consistent everywhere a "are you sure?" is needed.
 */
export function ConfirmDialog( { open, onOpenChange, title, description, confirmLabel = 'Confirm', cancelLabel = 'Cancel', destructive = false, onConfirm, loading = false } ) {
	return (
		<Dialog open={ open } onOpenChange={ onOpenChange }>
			<DialogContent className="am-max-w-sm">
				<DialogHeader>
					<DialogTitle>{ title }</DialogTitle>
					{ description && <DialogDescription>{ description }</DialogDescription> }
				</DialogHeader>
				<DialogFooter>
					<DialogClose asChild>
						<Button variant="outline" size="sm">{ cancelLabel }</Button>
					</DialogClose>
					<Button variant={ destructive ? 'destructive' : 'default' } size="sm" onClick={ onConfirm } disabled={ loading }>
						{ loading ? 'Please wait…' : confirmLabel }
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
}
