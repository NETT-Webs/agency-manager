import { Sidebar } from './sidebar';
import { Topbar } from './topbar';
import { ToastProvider } from '../ui/toast';

/**
 * `fullBleed` drops the default max-w-6xl/mx-auto/p-6 content constraint for
 * screens that need the entire content area (e.g. the Form Builder's
 * three-panel workspace) — the sidebar/topbar (the only navigation chrome
 * this app renders) stay exactly the same either way, so this never
 * duplicates navigation or touches WordPress's own admin chrome.
 */
export function Shell( { title, children, fullBleed } ) {
	return (
		<ToastProvider>
			<div className="am-flex am-h-screen am-w-full am-overflow-hidden am-bg-secondary/40">
				<Sidebar />
				<div className="am-flex am-min-w-0 am-flex-1 am-flex-col">
					<Topbar title={ title } />
					{ fullBleed ? (
						<main className="am-flex am-min-h-0 am-flex-1 am-flex-col am-overflow-hidden">{ children }</main>
					) : (
						<main className="am-flex-1 am-overflow-y-auto am-p-6">
							<div className="am-mx-auto am-max-w-6xl">{ children }</div>
						</main>
					) }
				</div>
			</div>
		</ToastProvider>
	);
}
