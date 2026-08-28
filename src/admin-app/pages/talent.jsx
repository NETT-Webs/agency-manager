import { Users } from 'lucide-react';
import { ContentList } from '../components/content-list';
import { getTalentList, links } from '../lib/api';

export function Talent() {
	return (
		<ContentList
			title="Talent"
			description="Search, filter, and manage every Talent profile."
			icon={ Users }
			fetcher={ getTalentList }
			addLabel="Add Talent"
			addUrl={ links.talentAdd }
			termLabel="Category"
			shortcodeGroups={ [ 'talent' ] }
		/>
	);
}
