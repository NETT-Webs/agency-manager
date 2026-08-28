import { useEffect, useState } from 'react';
import { Users, MapPin, Star, CheckCircle2, Inbox, ThumbsUp, Plus, FileText, AlertTriangle } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Skeleton } from '../components/ui/skeleton';
import { EmptyState } from '../components/ui/empty-state';
import { ShortcodesCard } from '../components/shortcodes-card';
import { getDashboardStats, getRecentActivity, links } from '../lib/api';

const TYPE_LABELS = { talent: 'Talent', location: 'Location' };

function StatCard( { icon: Icon, label, value, loading } ) {
	return (
		<Card>
			<CardContent className="am-flex am-items-center am-gap-4 am-p-4">
				<div className="am-flex am-h-10 am-w-10 am-shrink-0 am-items-center am-justify-center am-rounded-md am-bg-accent am-text-accent-foreground">
					<Icon className="am-h-5 am-w-5" aria-hidden="true" />
				</div>
				<div className="am-min-w-0">
					{ loading ? (
						<Skeleton className="am-h-7 am-w-12" />
					) : (
						<div className="am-text-2xl am-font-semibold am-leading-none am-text-foreground">{ value }</div>
					) }
					<div className="am-mt-1 am-truncate am-text-xs am-text-muted-foreground">{ label }</div>
				</div>
			</CardContent>
		</Card>
	);
}

function ErrorNotice( { message } ) {
	return (
		<div className="am-flex am-items-center am-gap-2 am-rounded-md am-border am-border-destructive/30 am-bg-destructive/10 am-p-3 am-text-sm am-text-destructive">
			<AlertTriangle className="am-h-4 am-w-4 am-shrink-0" aria-hidden="true" />
			{ message }
		</div>
	);
}

export function Dashboard() {
	const [ stats, setStats ] = useState( null );
	const [ activity, setActivity ] = useState( null );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		Promise.all( [ getDashboardStats(), getRecentActivity() ] )
			.then( ( [ statsData, activityData ] ) => {
				setStats( statsData );
				setActivity( activityData );
			} )
			.catch( () => setError( 'Could not load dashboard data. Please refresh the page.' ) );
	}, [] );

	const loading = ! stats;

	const tiles = stats
		? [
				{ icon: Users, label: 'Total Talent', value: stats.talent.total },
				{ icon: CheckCircle2, label: 'Active Talent', value: stats.talent.active },
				{ icon: Star, label: 'Featured Talent', value: stats.talent.featured },
				{ icon: MapPin, label: 'Total Locations', value: stats.locations.total },
				{ icon: CheckCircle2, label: 'Active Locations', value: stats.locations.active },
				{ icon: Star, label: 'Featured Locations', value: stats.locations.featured },
				{ icon: Inbox, label: 'Pending Applications', value: stats.applications.pending },
				{ icon: ThumbsUp, label: 'Approved Applications', value: stats.applications.approved },
		  ]
		: Array.from( { length: 8 } );

	return (
		<div className="am-flex am-flex-col am-gap-6">
			{ error && <ErrorNotice message={ error } /> }

			<div className="am-grid am-grid-cols-2 am-gap-4 sm:am-grid-cols-4">
				{ tiles.map( ( tile, i ) => (
					<StatCard key={ tile ? tile.label : i } { ...tile } icon={ tile ? tile.icon : Users } label={ tile ? tile.label : '' } loading={ loading } />
				) ) }
			</div>

			<div className="am-grid am-grid-cols-1 am-gap-6 lg:am-grid-cols-3">
				<Card className="lg:am-col-span-1">
					<CardHeader>
						<CardTitle>Quick Actions</CardTitle>
					</CardHeader>
					<CardContent className="am-flex am-flex-col am-gap-2">
						<Button as="a" href={ links.talentAdd } className="am-justify-start">
							<Plus className="am-h-4 am-w-4" aria-hidden="true" /> Add Talent
						</Button>
						<Button as="a" href={ links.locationAdd } className="am-justify-start" variant="secondary">
							<Plus className="am-h-4 am-w-4" aria-hidden="true" /> Add Location
						</Button>
						<Button as="a" href={ links.forms } className="am-justify-start" variant="secondary">
							<FileText className="am-h-4 am-w-4" aria-hidden="true" /> Create Form
						</Button>
						<Button as="a" href={ links.applications } className="am-justify-start" variant="outline">
							<Inbox className="am-h-4 am-w-4" aria-hidden="true" /> View Applications
						</Button>
					</CardContent>
				</Card>

				<Card className="lg:am-col-span-2">
					<CardHeader>
						<CardTitle>Recent Activity</CardTitle>
					</CardHeader>
					<CardContent>
						{ loading ? (
							<div className="am-flex am-flex-col am-gap-2">
								{ [ 0, 1, 2, 3 ].map( ( i ) => <Skeleton key={ i } className="am-h-8 am-w-full" /> ) }
							</div>
						) : activity.length === 0 ? (
							<EmptyState
								title="No activity yet"
								description="New talent, locations, and applications will show up here."
							/>
						) : (
							<ul className="am-flex am-flex-col am-divide-y am-divide-border">
								{ activity.map( ( item, i ) => (
									<li key={ i } className="am-flex am-items-center am-justify-between am-py-2 am-text-sm">
										<a href={ item.url } className="am-truncate am-font-medium am-text-foreground hover:am-underline">
											{ item.title }
										</a>
										<span className="am-shrink-0 am-pl-3 am-text-xs am-text-muted-foreground">
											{ TYPE_LABELS[ item.type ] || item.type } &middot; { item.date }
										</span>
									</li>
								) ) }
							</ul>
						) }
					</CardContent>
				</Card>
			</div>

			<ShortcodesCard title="Shortcode Reference" />
		</div>
	);
}
