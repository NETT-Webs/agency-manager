import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '../ui/select';

/**
 * A <select> whose current value might not be one of the known option
 * keys — the real value for existing records on this install can come from
 * a theme's own data (e.g. "Mains Power") that predates this plugin's own
 * option vocabulary. Rather than silently dropping that value (which the
 * REST layer would then read back as "changed" and overwrite), it's kept
 * selectable as its own synthetic option so leaving it alone round-trips
 * unchanged. See Profile_Rest_Controller::sanitize_meta_value()'s doc for
 * the write-side half of this same safety guarantee.
 */
export function MetaSelect( { value, onChange, options, placeholder = '—' } ) {
	const isKnown = '' === value || Object.prototype.hasOwnProperty.call( options, value );

	return (
		<Select value={ value || '__empty__' } onValueChange={ ( v ) => onChange( '__empty__' === v ? '' : v ) }>
			<SelectTrigger><SelectValue placeholder={ placeholder } /></SelectTrigger>
			<SelectContent>
				<SelectItem value="__empty__">{ placeholder }</SelectItem>
				{ ! isKnown && <SelectItem value={ value }>{ value } (current)</SelectItem> }
				{ Object.entries( options ).map( ( [ key, label ] ) => <SelectItem key={ key } value={ key }>{ label }</SelectItem> ) }
			</SelectContent>
		</Select>
	);
}
