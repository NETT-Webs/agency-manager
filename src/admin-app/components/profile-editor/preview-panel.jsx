import { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../ui/card';
import { Skeleton } from '../ui/skeleton';
import { EmptyState } from '../ui/empty-state';

/**
 * Renders the actual public card markup (Frontend\Card_Renderer, the same
 * template `/talent/` and `/locations/` use) inside an iframe — loading the
 * live theme stylesheet so it looks like the real site, not a lookalike
 * reimplementation. Only reflects the last-saved state (same "Save to
 * refresh the preview" limitation the classic meta box's preview tab has).
 */
export function PreviewPanel( { recordId, fetcher, refreshKey } ) {
	const [ state, setState ] = useState( null ); // { html, themeCssUrl, pluginCssUrl } | 'empty' | 'error'

	useEffect( () => {
		if ( ! recordId ) {
			setState( 'empty' );
			return;
		}
		setState( null );
		fetcher( recordId ).then( setState ).catch( () => setState( 'error' ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ recordId, refreshKey ] );

	return (
		<Card>
			<CardHeader><CardTitle>Preview</CardTitle></CardHeader>
			<CardContent>
				{ null === state && <Skeleton className="am-h-48 am-w-full" /> }
				{ 'empty' === state && (
					<EmptyState title="Save to see a preview" description="The card preview appears here once this record has been saved for the first time." className="am-border-none am-p-4" />
				) }
				{ 'error' === state && (
					<EmptyState title="Could not load preview" description="Try saving again." className="am-border-none am-p-4" />
				) }
				{ state && 'object' === typeof state && (
					<iframe
						title="Card preview"
						className="am-h-56 am-w-full am-rounded-md am-border am-border-border am-bg-white"
						srcDoc={ `<!doctype html><html><head><base target="_blank"><link rel="stylesheet" href="${ state.themeCssUrl }"><link rel="stylesheet" href="${ state.pluginCssUrl }"><style>body{margin:16px;font-family:sans-serif;}</style></head><body>${ state.html }</body></html>` }
					/>
				) }
			</CardContent>
		</Card>
	);
}
