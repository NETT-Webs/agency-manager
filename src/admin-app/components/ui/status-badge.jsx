import { Badge } from './badge';

/**
 * Plain-English workflow status → badge variant, shared by Applications and
 * anywhere else a submission/publish status is shown. Keeping the map here
 * (rather than inline per-screen) means the colour for "Approved" etc. can't
 * drift between screens.
 */
const STATUS_CONFIG = {
	submitted: { label: 'Submitted', variant: 'outline' },
	review: { label: 'In Review', variant: 'warning' },
	approved: { label: 'Approved', variant: 'success' },
	published: { label: 'Published', variant: 'default' },
	rejected: { label: 'Rejected', variant: 'destructive' },
	archived: { label: 'Archived', variant: 'secondary' },
};

export function StatusBadge( { status, className } ) {
	const config = STATUS_CONFIG[ status ] || { label: status, variant: 'outline' };
	return <Badge variant={ config.variant } className={ className }>{ config.label }</Badge>;
}

export function statusLabel( status ) {
	return ( STATUS_CONFIG[ status ] || { label: status } ).label;
}
