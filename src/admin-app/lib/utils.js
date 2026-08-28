import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Standard shadcn className helper: merges conditional classes, resolving Tailwind conflicts sanely. */
export function cn( ...inputs ) {
	return twMerge( clsx( inputs ) );
}
