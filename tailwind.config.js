/**
 * Scoped entirely to the Agency Manager React root (`#agency-manager-root`,
 * every generated class prefixed `am-`) so Tailwind's utility classes never
 * collide with wp-admin's own styles or another plugin's CSS on the same
 * page.
 */
module.exports = {
	prefix: 'am-',
	important: '#agency-manager-root',
	content: ['./src/**/*.{js,jsx}'],
	theme: {
		extend: {
			colors: {
				border: 'hsl(var(--am-border))',
				input: 'hsl(var(--am-input))',
				ring: 'hsl(var(--am-ring))',
				background: 'hsl(var(--am-background))',
				foreground: 'hsl(var(--am-foreground))',
				primary: {
					DEFAULT: 'hsl(var(--am-primary))',
					foreground: 'hsl(var(--am-primary-foreground))',
				},
				secondary: {
					DEFAULT: 'hsl(var(--am-secondary))',
					foreground: 'hsl(var(--am-secondary-foreground))',
				},
				muted: {
					DEFAULT: 'hsl(var(--am-muted))',
					foreground: 'hsl(var(--am-muted-foreground))',
				},
				accent: {
					DEFAULT: 'hsl(var(--am-accent))',
					foreground: 'hsl(var(--am-accent-foreground))',
				},
				destructive: {
					DEFAULT: 'hsl(var(--am-destructive))',
					foreground: 'hsl(var(--am-destructive-foreground))',
				},
				card: {
					DEFAULT: 'hsl(var(--am-card))',
					foreground: 'hsl(var(--am-card-foreground))',
				},
			},
			borderRadius: {
				lg: 'var(--am-radius)',
				md: 'calc(var(--am-radius) - 2px)',
				sm: 'calc(var(--am-radius) - 4px)',
			},
		},
	},
	corePlugins: {
		preflight: false, // never reset wp-admin's own base styles globally.
	},
	plugins: [],
};
