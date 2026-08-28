import { currentUser, links } from '../../lib/api';

export function Topbar( { title } ) {
	return (
		<header className="am-flex am-h-14 am-items-center am-justify-between am-border-b am-border-border am-bg-card am-px-6">
			<div>
				<h1 className="am-text-base am-font-semibold am-text-foreground">{ title }</h1>
			</div>
			<div className="am-flex am-items-center am-gap-3">
				{ currentUser.name && (
					<a
						href={ links.profile || '#' }
						className="am-flex am-items-center am-gap-2 am-rounded-md am-px-2 am-py-1.5 am-text-sm am-text-foreground/80 hover:am-bg-accent"
					>
						{ currentUser.avatarUrl && (
							<img
								src={ currentUser.avatarUrl }
								alt=""
								className="am-h-6 am-w-6 am-rounded-full"
							/>
						) }
						<span>{ currentUser.name }</span>
					</a>
				) }
			</div>
		</header>
	);
}
