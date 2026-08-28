import { useEffect, useMemo, useState } from 'react';
import { Plus, ExternalLink, Pencil, Star, ImageOff } from 'lucide-react';
import { PageHeader } from './ui/page-header';
import { Card, CardContent } from './ui/card';
import { SearchInput } from './ui/search-input';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from './ui/select';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from './ui/table';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { Skeleton } from './ui/skeleton';
import { EmptyState } from './ui/empty-state';
import { useToast } from './ui/toast';
import { ShortcodesCard } from './shortcodes-card';

// Plain-English labels for WordPress's internal post_status values — a
// non-technical agency owner should never see the raw "publish"/"draft" strings.
const STATUS_LABELS = { publish: 'Published', draft: 'Draft', pending: 'Pending Review', private: 'Private' };

/**
 * Shared search/filter/status list used by both the Talent and Locations
 * screens (Phases 5/6) — same read-model shape from Content_Rest_Controller,
 * same interactions, different copy/icon/add-link. Editing/adding still
 * happens on WordPress's native post-edit screen (see Admin_App_Page doc).
 */
export function ContentList( { title, description, icon: Icon, fetcher, addLabel, addUrl, termLabel, shortcodeGroups } ) {
	const toast = useToast();
	const [ rows, setRows ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ termFilter, setTermFilter ] = useState( 'all' );

	useEffect( () => {
		fetcher().then( setRows ).catch( () => toast( `Could not load ${ title.toLowerCase() }.`, 'error' ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const allTerms = useMemo( () => {
		if ( ! rows ) {
			return [];
		}
		const names = new Set();
		rows.forEach( ( r ) => r.terms.forEach( ( t ) => names.add( t.name ) ) );
		return Array.from( names ).sort();
	}, [ rows ] );

	const filtered = useMemo( () => {
		if ( ! rows ) {
			return [];
		}
		return rows.filter( ( r ) => {
			if ( statusFilter === 'active' && ! r.active ) return false;
			if ( statusFilter === 'inactive' && r.active ) return false;
			if ( statusFilter === 'featured' && ! r.featured ) return false;
			if ( termFilter !== 'all' && ! r.terms.some( ( t ) => t.name === termFilter ) ) return false;
			if ( search && ! r.title.toLowerCase().includes( search.toLowerCase() ) ) return false;
			return true;
		} );
	}, [ rows, search, statusFilter, termFilter ] );

	const loading = ! rows;

	return (
		<div className="am-flex am-flex-col am-gap-6">
			<PageHeader
				title={ title }
				description={ description }
				actions={ <Button as="a" href={ addUrl } size="sm"><Plus className="am-h-4 am-w-4" aria-hidden="true" /> { addLabel }</Button> }
			/>

			<div className="am-flex am-flex-wrap am-items-center am-gap-3">
				<SearchInput value={ search } onChange={ setSearch } placeholder={ `Search ${ title.toLowerCase() }…` } />
				<Select value={ statusFilter } onValueChange={ setStatusFilter }>
					<SelectTrigger className="am-w-40"><SelectValue /></SelectTrigger>
					<SelectContent>
						<SelectItem value="all">All</SelectItem>
						<SelectItem value="active">Active</SelectItem>
						<SelectItem value="inactive">Inactive</SelectItem>
						<SelectItem value="featured">Featured</SelectItem>
					</SelectContent>
				</Select>
				{ allTerms.length > 0 && (
					<Select value={ termFilter } onValueChange={ setTermFilter }>
						<SelectTrigger className="am-w-44"><SelectValue /></SelectTrigger>
						<SelectContent>
							<SelectItem value="all">{ `All ${ termLabel }` }</SelectItem>
							{ allTerms.map( ( t ) => <SelectItem key={ t } value={ t }>{ t }</SelectItem> ) }
						</SelectContent>
					</Select>
				) }
			</div>

			<Card>
				<CardContent className="am-p-0">
					{ loading ? (
						<div className="am-flex am-flex-col am-gap-2 am-p-4">
							{ [ 0, 1, 2 ].map( ( i ) => <Skeleton key={ i } className="am-h-12 am-w-full" /> ) }
						</div>
					) : filtered.length === 0 ? (
						<EmptyState
							icon={ Icon }
							title={ rows.length === 0 ? `No ${ title.toLowerCase() } yet` : 'No matches' }
							description={ rows.length === 0 ? `Get started by adding your first ${ title.toLowerCase().replace( /s$/, '' ) }.` : 'Try a different search or filter.' }
							action={ rows.length === 0 && <Button as="a" href={ addUrl } size="sm"><Plus className="am-h-4 am-w-4" aria-hidden="true" /> { addLabel }</Button> }
							className="am-border-none"
						/>
					) : (
						<Table>
							<TableHeader>
								<TableRow>
									<TableHead />
									<TableHead>Name</TableHead>
									<TableHead>{ termLabel }</TableHead>
									<TableHead>Status</TableHead>
									<TableHead className="am-text-right">Actions</TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								{ filtered.map( ( row ) => (
									<TableRow key={ row.id }>
										<TableCell>
											{ row.thumbnail ? (
												<img src={ row.thumbnail } alt="" className="am-h-9 am-w-9 am-rounded-md am-object-cover" />
											) : (
												<div className="am-flex am-h-9 am-w-9 am-items-center am-justify-center am-rounded-md am-bg-secondary am-text-muted-foreground">
													<ImageOff className="am-h-4 am-w-4" aria-hidden="true" />
												</div>
											) }
										</TableCell>
										<TableCell>
											<div className="am-flex am-items-center am-gap-1.5 am-font-medium am-text-foreground">
												{ row.title }
												{ row.featured && <Star className="am-h-3.5 am-w-3.5 am-fill-amber-400 am-text-amber-400" aria-hidden="true" /> }
											</div>
										</TableCell>
										<TableCell className="am-text-muted-foreground">{ row.terms.map( ( t ) => t.name ).join( ', ' ) || '—' }</TableCell>
										<TableCell>
											<div className="am-flex am-flex-wrap am-gap-1">
												<Badge variant={ 'publish' === row.status ? 'success' : 'secondary' }>{ STATUS_LABELS[ row.status ] || row.status }</Badge>
												{ ! row.active && <Badge variant="outline">Inactive</Badge> }
											</div>
										</TableCell>
										<TableCell className="am-text-right">
											<div className="am-flex am-justify-end am-gap-1">
												<Button as="a" href={ row.editUrl } size="sm" variant="outline"><Pencil className="am-h-3.5 am-w-3.5" aria-hidden="true" /> Edit</Button>
												{ 'publish' === row.status && (
													<Button as="a" href={ row.viewUrl } target="_blank" rel="noopener" size="sm" variant="ghost">
														<ExternalLink className="am-h-3.5 am-w-3.5" aria-hidden="true" />
													</Button>
												) }
											</div>
										</TableCell>
									</TableRow>
								) ) }
							</TableBody>
						</Table>
					) }
				</CardContent>
			</Card>

			{ shortcodeGroups && <ShortcodesCard groupKeys={ shortcodeGroups } title={ `${ title } Shortcodes` } /> }
		</div>
	);
}
