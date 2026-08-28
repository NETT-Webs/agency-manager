import { MapPin } from 'lucide-react';
import { ContentList } from '../components/content-list';
import { getLocationsList, links } from '../lib/api';

export function Locations() {
	return (
		<ContentList
			title="Locations"
			description="Search, filter, and manage every Location listing."
			icon={ MapPin }
			fetcher={ getLocationsList }
			addLabel="Add Location"
			addUrl={ links.locationAdd }
			termLabel="Type"
			shortcodeGroups={ [ 'locations' ] }
		/>
	);
}
