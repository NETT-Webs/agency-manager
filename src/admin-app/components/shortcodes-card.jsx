import { useEffect, useState } from 'react';
import { Copy, Check } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from './ui/card';
import { SearchInput } from './ui/search-input';
import { Button } from './ui/button';
import { Skeleton } from './ui/skeleton';
import { getShortcodeGroups } from '../lib/api';

function CopyButton( { text } ) {
	const [ copied, setCopied ] = useState( false );

	function handleCopy() {
		navigator.clipboard.writeText( text ).then( () => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 1500 );
		} );
	}

	return (
		<Button variant="outline" size="sm" onClick={ handleCopy } aria-label={ `Copy ${ text }` }>
			{ copied ? <Check className="am-h-3.5 am-w-3.5" aria-hidden="true" /> : <Copy className="am-h-3.5 am-w-3.5" aria-hidden="true" /> }
			{ copied ? 'Copied' : 'Copy' }
		</Button>
	);
}

/**
 * The same grouped shortcode reference the Dashboard already surfaces
 * (Admin\Shortcode_Reference::groups(), via the shared /shortcodes REST
 * route), scoped to just the groups relevant to whichever screen it's
 * placed on — Talent/Locations/Forms/Applications each show only their
 * own shortcodes instead of the full reference.
 */
export function ShortcodesCard( { groupKeys, title = 'Shortcodes' } ) {
	const [ groups, setGroups ] = useState( null );
	const [ search, setSearch ] = useState( '' );

	useEffect( () => {
		getShortcodeGroups().then( setGroups ).catch( () => {} );
	}, [] );

	if ( ! groups ) {
		return (
			<Card>
				<CardHeader><CardTitle>{ title }</CardTitle></CardHeader>
				<CardContent><Skeleton className="am-h-24 am-w-full" /></CardContent>
			</Card>
		);
	}

	const entries = Object.entries( groups ).filter( ( [ key ] ) => ! groupKeys || groupKeys.includes( key ) );
	const q = search.toLowerCase();

	return (
		<Card>
			<CardHeader className="am-flex-row am-items-center am-justify-between am-space-y-0">
				<CardTitle>{ title }</CardTitle>
				<SearchInput value={ search } onChange={ setSearch } placeholder="Search shortcodes…" className="am-max-w-[220px]" />
			</CardHeader>
			<CardContent>
				<div className="am-flex am-flex-col am-gap-4">
					{ entries.map( ( [ groupKey, group ] ) => {
						const shortcodes = group.shortcodes.filter(
							( sc ) => ! q || sc.tag.toLowerCase().includes( q ) || sc.description.toLowerCase().includes( q )
						);
						if ( shortcodes.length === 0 ) {
							return null;
						}
						return (
							<div key={ groupKey }>
								{ ! groupKeys && <h4 className="am-mb-2 am-text-xs am-font-semibold am-uppercase am-text-muted-foreground">{ group.label }</h4> }
								<div className="am-flex am-flex-col am-gap-2">
									{ shortcodes.map( ( sc, i ) => (
										<div key={ `${ sc.tag }-${ i }` } className="am-flex am-flex-col am-gap-1 am-rounded-md am-border am-border-border am-px-3 am-py-2 sm:am-flex-row sm:am-items-center sm:am-justify-between">
											<div className="am-min-w-0">
												<code className="am-text-xs am-text-foreground">{ sc.example }</code>
												<p className="am-mt-0.5 am-text-xs am-text-muted-foreground">{ sc.description }</p>
												{ sc.when && <p className="am-mt-0.5 am-text-xs am-text-muted-foreground/80">{ sc.when }</p> }
											</div>
											<CopyButton text={ sc.example } />
										</div>
									) ) }
								</div>
							</div>
						);
					} ) }
				</div>
			</CardContent>
		</Card>
	);
}
