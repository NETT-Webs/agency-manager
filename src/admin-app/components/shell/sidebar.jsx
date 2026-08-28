import { useEffect, useState } from 'react';
import {
	LayoutDashboard,
	Users,
	MapPin,
	Inbox,
	FileText,
	MonitorSmartphone,
	ArrowLeftRight,
	Settings as SettingsIcon,
	HelpCircle,
	ChevronDown,
	ChevronsLeft,
	ChevronsRight,
	Plus,
	List,
	Tags,
	FolderTree,
} from 'lucide-react';
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '../ui/tooltip';
import { links, screen as rawScreen } from '../../lib/api';
import { cn } from '../../lib/utils';

// The Add/Edit editor screens belong to their list's nav item for active-state purposes.
const SCREEN_GROUP = { 'talent-edit': 'talent', 'location-edit': 'locations', 'csv-import': 'import-export', 'form-builder': 'forms' };
const currentScreen = SCREEN_GROUP[ rawScreen ] || rawScreen;

/**
 * Talent/Locations link to their own new React list + editor screens
 * (search, filters, status; Add/Edit is now a React screen too — see
 * pages/talent-editor.jsx / location-editor.jsx). "Categories"/"Groups"
 * still go to WordPress's native taxonomy screens, since term management
 * itself wasn't part of this redesign. Every other top-level item is now a
 * React screen in its own right (see Admin_App_Page + app.js); each is
 * still a full page navigation to its own admin-menu URL, matching how
 * wp-admin already works — there is no client-side router.
 */
function navSections() {
	return [
		{ key: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: links.dashboard },
		{
			key: 'talent',
			label: 'Talent',
			icon: Users,
			href: links.talent,
			children: [
				{ label: 'All Talent', icon: List, href: links.talent },
				{ label: 'Add Talent', icon: Plus, href: links.talentAdd },
				{ label: 'Categories', icon: Tags, href: links.talentCategories },
				{ label: 'Groups', icon: FolderTree, href: links.talentGroups },
			],
		},
		{
			key: 'locations',
			label: 'Locations',
			icon: MapPin,
			href: links.locations,
			children: [
				{ label: 'All Locations', icon: List, href: links.locations },
				{ label: 'Add Location', icon: Plus, href: links.locationAdd },
				{ label: 'Categories', icon: Tags, href: links.locationCategories },
			],
		},
		{ key: 'applications', label: 'Applications', icon: Inbox, href: links.applications },
		{ key: 'forms', label: 'Forms', icon: FileText, href: links.forms },
		{ key: 'display', label: 'Website Display', icon: MonitorSmartphone, href: links.display },
		{ key: 'import-export', label: 'Import / Export', icon: ArrowLeftRight, href: links.importExport },
		{ key: 'settings', label: 'Settings', icon: SettingsIcon, href: links.settings },
	];
}

function NavLink( { icon: Icon, label, href, collapsed, active } ) {
	const content = (
		<a
			href={ href }
			aria-current={ active ? 'page' : undefined }
			className={ cn(
				'am-flex am-items-center am-gap-3 am-rounded-md am-px-3 am-py-2 am-text-sm am-font-medium am-transition-colors',
				active ? 'am-bg-accent am-text-accent-foreground' : 'am-text-foreground/80 hover:am-bg-accent hover:am-text-accent-foreground'
			) }
		>
			<Icon className="am-h-4 am-w-4 am-shrink-0" aria-hidden="true" />
			{ ! collapsed && <span className="am-truncate">{ label }</span> }
		</a>
	);

	if ( ! collapsed ) {
		return content;
	}

	return (
		<Tooltip>
			<TooltipTrigger asChild>{ content }</TooltipTrigger>
			<TooltipContent side="right">{ label }</TooltipContent>
		</Tooltip>
	);
}

function NavGroup( { section, collapsed, active } ) {
	const [ open, setOpen ] = useState( active );
	const Icon = section.icon;

	if ( collapsed ) {
		// Collapsed mode: each child becomes its own tooltip'd icon-only link,
		// grouped visually by a divider — keeps the group's items reachable
		// without a flyout menu (simplest accessible behaviour for Phase 1).
		return (
			<div className="am-flex am-flex-col am-gap-1 am-border-t am-border-border am-pt-1 first:am-border-t-0 first:am-pt-0">
				{ section.children.map( ( child ) => (
					<NavLink key={ child.label } { ...child } collapsed />
				) ) }
			</div>
		);
	}

	return (
		<div>
			<button
				type="button"
				onClick={ () => setOpen( ( v ) => ! v ) }
				aria-expanded={ open }
				className={ cn(
					'am-flex am-w-full am-items-center am-justify-between am-rounded-md am-px-3 am-py-2 am-text-sm am-font-medium am-transition-colors',
					active ? 'am-bg-accent am-text-accent-foreground' : 'am-text-foreground/80 hover:am-bg-accent hover:am-text-accent-foreground'
				) }
			>
				<span className="am-flex am-items-center am-gap-3">
					<Icon className="am-h-4 am-w-4" aria-hidden="true" />
					{ section.label }
				</span>
				<ChevronDown className={ cn( 'am-h-4 am-w-4 am-transition-transform', open && 'am-rotate-180' ) } aria-hidden="true" />
			</button>
			{ open && (
				<div className="am-ml-4 am-mt-1 am-flex am-flex-col am-gap-1 am-border-l am-border-border am-pl-3">
					{ section.children.map( ( child ) => (
						<a
							key={ child.label }
							href={ child.href }
							className="am-flex am-items-center am-gap-2 am-rounded-md am-px-2 am-py-1.5 am-text-sm am-text-foreground/70 hover:am-bg-accent hover:am-text-accent-foreground"
						>
							<child.icon className="am-h-3.5 am-w-3.5" aria-hidden="true" />
							{ child.label }
						</a>
					) ) }
				</div>
			) }
		</div>
	);
}

const MOBILE_BREAKPOINT = 768;

export function Sidebar() {
	const [ collapsed, setCollapsed ] = useState(
		() => window.innerWidth < MOBILE_BREAKPOINT || localStorage.getItem( 'am-sidebar-collapsed' ) === '1'
	);

	// Auto-collapse on narrow viewports (tablet/mobile) regardless of the
	// user's saved preference, so the content area never has to fight the
	// sidebar for space — still manually re-expandable via the toggle below.
	useEffect( () => {
		function handleResize() {
			if ( window.innerWidth < MOBILE_BREAKPOINT ) {
				setCollapsed( true );
			}
		}
		window.addEventListener( 'resize', handleResize );
		return () => window.removeEventListener( 'resize', handleResize );
	}, [] );

	function toggle() {
		setCollapsed( ( prev ) => {
			const next = ! prev;
			localStorage.setItem( 'am-sidebar-collapsed', next ? '1' : '0' );
			return next;
		} );
	}

	return (
		<TooltipProvider delayDuration={ 200 }>
			<aside
				className={ cn(
					'am-flex am-h-full am-flex-col am-border-r am-border-border am-bg-card am-transition-[width]',
					collapsed ? 'am-w-16' : 'am-w-64'
				) }
			>
				<div className="am-flex am-h-14 am-items-center am-gap-2 am-border-b am-border-border am-px-4">
					<div className="am-flex am-h-7 am-w-7 am-shrink-0 am-items-center am-justify-center am-rounded-md am-bg-primary am-text-sm am-font-bold am-text-primary-foreground">
						A
					</div>
					{ ! collapsed && <span className="am-truncate am-text-sm am-font-semibold">Agency Manager</span> }
				</div>

				<nav className="am-flex am-flex-1 am-flex-col am-gap-1 am-overflow-y-auto am-p-2">
					{ navSections().map( ( section ) =>
						section.children ? (
							<NavGroup key={ section.key } section={ section } collapsed={ collapsed } active={ currentScreen === section.key } />
						) : (
							<NavLink key={ section.key } { ...section } collapsed={ collapsed } active={ currentScreen === section.key } />
						)
					) }
				</nav>

				<div className="am-flex am-flex-col am-gap-1 am-border-t am-border-border am-p-2">
					<NavLink icon={ HelpCircle } label="Help / Documentation" href={ links.help || '#' } collapsed={ collapsed } />
					<button
						type="button"
						onClick={ toggle }
						className="am-flex am-items-center am-gap-3 am-rounded-md am-px-3 am-py-2 am-text-sm am-font-medium am-text-foreground/70 hover:am-bg-accent hover:am-text-accent-foreground"
						aria-label={ collapsed ? 'Expand sidebar' : 'Collapse sidebar' }
					>
						{ collapsed ? <ChevronsRight className="am-h-4 am-w-4" aria-hidden="true" /> : <ChevronsLeft className="am-h-4 am-w-4" aria-hidden="true" /> }
						{ ! collapsed && <span>Collapse</span> }
					</button>
				</div>
			</aside>
		</TooltipProvider>
	);
}
