import { createContext, useCallback, useContext, useState } from 'react';
import { CheckCircle2, AlertTriangle, Info, X } from 'lucide-react';
import { cn } from '../../lib/utils';

const ToastContext = createContext( null );

const ICONS = { success: CheckCircle2, error: AlertTriangle, info: Info };
const STYLES = {
	success: 'am-border-green-200 am-bg-green-50 am-text-green-800',
	error: 'am-border-destructive/30 am-bg-destructive/10 am-text-destructive',
	info: 'am-border-border am-bg-card am-text-foreground',
};

let idSeq = 0;

/** Mounted once in Shell; every screen calls useToast() to surface success/error feedback after a mutation. */
export function ToastProvider( { children } ) {
	const [ toasts, setToasts ] = useState( [] );

	const dismiss = useCallback( ( id ) => {
		setToasts( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
	}, [] );

	const toast = useCallback( ( message, type = 'success' ) => {
		const id = ++idSeq;
		setToasts( ( prev ) => [ ...prev, { id, message, type } ] );
		setTimeout( () => dismiss( id ), 4000 );
	}, [ dismiss ] );

	return (
		<ToastContext.Provider value={ toast }>
			{ children }
			<div className="am-pointer-events-none am-fixed am-bottom-4 am-right-4 am-z-[100] am-flex am-flex-col am-gap-2">
				{ toasts.map( ( t ) => {
					const Icon = ICONS[ t.type ] || Info;
					return (
						<div
							key={ t.id }
							role="status"
							className={ cn(
								'am-pointer-events-auto am-flex am-w-80 am-items-start am-gap-2 am-rounded-md am-border am-p-3 am-text-sm am-shadow-lg am-toast-enter',
								STYLES[ t.type ] || STYLES.info
							) }
						>
							<Icon className="am-mt-0.5 am-h-4 am-w-4 am-shrink-0" aria-hidden="true" />
							<span className="am-flex-1">{ t.message }</span>
							<button type="button" onClick={ () => dismiss( t.id ) } aria-label="Dismiss" className="am-shrink-0 am-opacity-60 hover:am-opacity-100">
								<X className="am-h-3.5 am-w-3.5" aria-hidden="true" />
							</button>
						</div>
					);
				} ) }
			</div>
		</ToastContext.Provider>
	);
}

export function useToast() {
	const toast = useContext( ToastContext );
	if ( ! toast ) {
		throw new Error( 'useToast() must be used within <ToastProvider>' );
	}
	return toast;
}
