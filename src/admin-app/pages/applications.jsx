import { Fragment, useEffect, useMemo, useState } from 'react';
import { ChevronDown, ChevronRight, Inbox, ExternalLink } from 'lucide-react';
import { PageHeader } from '../components/ui/page-header';
import { Card, CardContent } from '../components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '../components/ui/tabs';
import { SearchInput } from '../components/ui/search-input';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../components/ui/select';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { Button } from '../components/ui/button';
import { StatusBadge } from '../components/ui/status-badge';
import { Skeleton } from '../components/ui/skeleton';
import { EmptyState } from '../components/ui/empty-state';
import { ConfirmDialog } from '../components/ui/confirm-dialog';
import { useToast } from '../components/ui/toast';
import { ShortcodesCard } from '../components/shortcodes-card';
import { getApplications, setApplicationStatus, publishApplication } from '../lib/api';

/** Plain-English next actions offered from each status, matching Forms\Workflow::STATUSES exactly. */
const NEXT_ACTIONS = {
	submitted: [ { status: 'review', label: 'Move to Review' } ],
	review: [
		{ status: 'approved', label: 'Approve' },
		{ status: 'rejected', label: 'Reject' },
	],
	approved: [
		{ status: 'publish', label: 'Publish' },
		{ status: 'rejected', label: 'Reject' },
	],
};

function formatValue( value ) {
	return Array.isArray( value ) ? value.map( String ).join( ', ' ) : String( value ?? '' );
}

function ApplicationDetail( { row } ) {
	return (
		<div className="am-flex am-flex-wrap am-gap-6 am-border-t am-border-border am-bg-secondary/30 am-p-4">
			<div className="am-min-w-[220px] am-flex-1">
				<h4 className="am-mb-2 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">Submitted Data</h4>
				<dl className="am-flex am-flex-col am-divide-y am-divide-border am-rounded-md am-border am-border-border am-bg-card">
					{ Object.entries( row.values ).map( ( [ key, value ] ) => (
						<div key={ key } className="am-flex am-gap-3 am-px-3 am-py-1.5 am-text-xs">
							<dt className="am-w-32 am-shrink-0 am-text-muted-foreground">{ key }</dt>
							<dd className="am-text-foreground">{ formatValue( value ) || '—' }</dd>
						</div>
					) ) }
				</dl>
			</div>
			<div className="am-min-w-[220px] am-flex-1">
				<h4 className="am-mb-2 am-text-xs am-font-semibold am-uppercase am-tracking-wide am-text-muted-foreground">Will Be Written To Profile</h4>
				{ row.mapped.length === 0 ? (
					<p className="am-text-xs am-text-muted-foreground">No fields on this form are mapped yet — nothing will be written beyond the name/photo when published.</p>
				) : (
					<dl className="am-flex am-flex-col am-divide-y am-divide-border am-rounded-md am-border am-border-border am-bg-card">
						{ row.mapped.map( ( m, i ) => (
							<div key={ i } className="am-flex am-gap-3 am-px-3 am-py-1.5 am-text-xs">
								<dt className="am-w-32 am-shrink-0 am-text-muted-foreground">{ m.target }</dt>
								<dd className="am-text-foreground">{ formatValue( m.value ) || '—' }</dd>
							</div>
						) ) }
					</dl>
				) }
			</div>
		</div>
	);
}

export function Applications() {
	const toast = useToast();
	const [ type, setType ] = useState( 'talent' );
	const [ rows, setRows ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ expanded, setExpanded ] = useState( null );
	const [ pending, setPending ] = useState( null ); // { id, action, label, destructive }
	const [ busyId, setBusyId ] = useState( null );

	function load( nextType ) {
		setRows( null );
		getApplications( nextType )
			.then( setRows )
			.catch( () => toast( 'Could not load applications.', 'error' ) );
	}

	useEffect( () => {
		load( type );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ type ] );

	const filtered = useMemo( () => {
		if ( ! rows ) {
			return [];
		}
		return rows.filter( ( r ) => {
			if ( statusFilter !== 'all' && r.status !== statusFilter ) {
				return false;
			}
			if ( ! search ) {
				return true;
			}
			const q = search.toLowerCase();
			return r.name.toLowerCase().includes( q ) || r.email.toLowerCase().includes( q ) || r.formTitle.toLowerCase().includes( q );
		} );
	}, [ rows, search, statusFilter ] );

	function requestAction( row, action ) {
		if ( action.status === 'publish' ) {
			setPending( { id: row.id, kind: 'publish', label: 'Publish this application?', description: `A new draft ${ type } profile will be created from "${ row.name }" and can be finished before it goes live.` } );
			return;
		}
		if ( action.status === 'rejected' || action.status === 'archived' ) {
			setPending( { id: row.id, kind: 'status', status: action.status, label: `${ action.label } this application?`, destructive: true } );
			return;
		}
		runAction( row.id, { kind: 'status', status: action.status } );
	}

	function runAction( id, action ) {
		setBusyId( id );
		const request = action.kind === 'publish' ? publishApplication( id ) : setApplicationStatus( id, action.status );

		request
			.then( () => {
				toast( action.kind === 'publish' ? 'Application published — a draft profile was created.' : 'Application updated.' );
				load( type );
			} )
			.catch( ( err ) => toast( err?.message || 'Something went wrong.', 'error' ) )
			.finally( () => {
				setBusyId( null );
				setPending( null );
			} );
	}

	const loading = ! rows;

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title="Applications"
				description="Review submissions from your public Talent and Location application forms, then approve and publish them into real profiles."
			/>

			<Tabs value={ type } onValueChange={ setType }>
				<TabsList>
					<TabsTrigger value="talent">Talent Applications</TabsTrigger>
					<TabsTrigger value="location">Location Applications</TabsTrigger>
				</TabsList>
			</Tabs>

			<div className="am-flex am-flex-wrap am-items-center am-gap-3">
				<SearchInput value={ search } onChange={ setSearch } placeholder="Search by name, email, or form…" />
				<Select value={ statusFilter } onValueChange={ setStatusFilter }>
					<SelectTrigger className="am-w-44">
						<SelectValue />
					</SelectTrigger>
					<SelectContent>
						<SelectItem value="all">All Statuses</SelectItem>
						<SelectItem value="submitted">Submitted</SelectItem>
						<SelectItem value="review">In Review</SelectItem>
						<SelectItem value="approved">Approved</SelectItem>
						<SelectItem value="published">Published</SelectItem>
						<SelectItem value="rejected">Rejected</SelectItem>
						<SelectItem value="archived">Archived</SelectItem>
					</SelectContent>
				</Select>
			</div>

			<Card>
				<CardContent className="am-p-0">
					{ loading ? (
						<div className="am-flex am-flex-col am-gap-2 am-p-4">
							{ [ 0, 1, 2 ].map( ( i ) => <Skeleton key={ i } className="am-h-10 am-w-full" /> ) }
						</div>
					) : filtered.length === 0 ? (
						<EmptyState
							icon={ Inbox }
							title="No applications found"
							description={ rows.length === 0 ? 'Applications appear here once visitors submit your public forms.' : 'Try a different search or status filter.' }
							className="am-border-none"
						/>
					) : (
						<Table>
							<TableHeader>
								<TableRow>
									<TableHead className="am-w-8" />
									<TableHead>Applicant</TableHead>
									<TableHead>Form</TableHead>
									<TableHead>Submitted</TableHead>
									<TableHead>Status</TableHead>
									<TableHead className="am-text-right">Actions</TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								{ filtered.map( ( row ) => (
									<Fragment key={ row.id }>
										<TableRow>
											<TableCell>
												<button
													type="button"
													onClick={ () => setExpanded( expanded === row.id ? null : row.id ) }
													aria-label={ expanded === row.id ? 'Collapse' : 'Expand' }
													className="am-text-muted-foreground hover:am-text-foreground"
												>
													{ expanded === row.id ? <ChevronDown className="am-h-4 am-w-4" /> : <ChevronRight className="am-h-4 am-w-4" /> }
												</button>
											</TableCell>
											<TableCell>
												<div className="am-font-medium am-text-foreground">{ row.name }</div>
												{ row.email && <div className="am-text-xs am-text-muted-foreground">{ row.email }</div> }
											</TableCell>
											<TableCell className="am-text-muted-foreground">{ row.formTitle || '—' }</TableCell>
											<TableCell className="am-text-muted-foreground">{ row.date }</TableCell>
											<TableCell><StatusBadge status={ row.status } /></TableCell>
											<TableCell className="am-text-right">
												<div className="am-flex am-justify-end am-gap-2">
													{ ( NEXT_ACTIONS[ row.status ] || [] ).map( ( action ) => (
														<Button
															key={ action.status }
															size="sm"
															variant={ action.status === 'rejected' ? 'outline' : 'default' }
															disabled={ busyId === row.id }
															onClick={ () => requestAction( row, action ) }
														>
															{ action.label }
														</Button>
													) ) }
													{ ! [ 'published', 'archived' ].includes( row.status ) && (
														<Button size="sm" variant="ghost" disabled={ busyId === row.id } onClick={ () => requestAction( row, { status: 'archived', label: 'Archive' } ) }>
															Archive
														</Button>
													) }
													{ row.publishedEditUrl && (
														<Button as="a" href={ row.publishedEditUrl } size="sm" variant="outline">
															Edit Profile <ExternalLink className="am-h-3 am-w-3" aria-hidden="true" />
														</Button>
													) }
												</div>
											</TableCell>
										</TableRow>
										{ expanded === row.id && (
											<TableRow>
												<TableCell colSpan={ 6 } className="am-p-0">
													<ApplicationDetail row={ row } />
												</TableCell>
											</TableRow>
										) }
									</Fragment>
								) ) }
							</TableBody>
						</Table>
					) }
				</CardContent>
			</Card>

			<ShortcodesCard groupKeys={ [ 'forms' ] } title="Application Form Shortcodes" />

			<ConfirmDialog
				open={ !! pending }
				onOpenChange={ ( open ) => ! open && setPending( null ) }
				title={ pending?.label || '' }
				description={ pending?.description }
				destructive={ pending?.destructive }
				confirmLabel={ pending?.kind === 'publish' ? 'Publish' : 'Confirm' }
				loading={ busyId === pending?.id }
				onConfirm={ () => runAction( pending.id, pending.kind === 'publish' ? { kind: 'publish' } : { kind: 'status', status: pending.status } ) }
			/>
		</div>
	);
}
