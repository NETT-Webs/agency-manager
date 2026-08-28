import * as TabsPrimitive from '@radix-ui/react-tabs';
import { cn } from '../../lib/utils';

export const Tabs = TabsPrimitive.Root;

export function TabsList( { className, ...props } ) {
	return (
		<TabsPrimitive.List
			className={ cn( 'am-inline-flex am-h-9 am-items-center am-gap-1 am-rounded-md am-bg-secondary am-p-1', className ) }
			{ ...props }
		/>
	);
}

export function TabsTrigger( { className, ...props } ) {
	return (
		<TabsPrimitive.Trigger
			className={ cn(
				'am-inline-flex am-items-center am-rounded-sm am-px-3 am-py-1 am-text-sm am-font-medium am-text-muted-foreground am-transition-colors',
				'hover:am-text-foreground',
				'data-[state=active]:am-bg-card data-[state=active]:am-text-foreground data-[state=active]:am-shadow-sm',
				className
			) }
			{ ...props }
		/>
	);
}

export function TabsContent( { className, ...props } ) {
	return <TabsPrimitive.Content className={ cn( 'am-mt-4 focus-visible:am-outline-none', className ) } { ...props } />;
}
