/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // Content area — driven by CSS variables for theme support
        bg:        'var(--bg)',
        surface:   'var(--surface)',
        surface2:  'var(--surface2)',
        border:    'var(--border)',
        border2:   'var(--border2)',
        text:      'var(--text)',
        text2:     'var(--text2)',
        text3:     'var(--text3)',
        // Accent — changes with theme
        accent:          'var(--accent)',
        'accent-light':  'var(--accent-light)',
        'accent-mid':    'var(--accent-mid)',
        'accent-border': 'var(--accent-border)',
        // Status — constant across themes
        green:          'var(--green)',
        'green-bg':     'var(--green-bg)',
        'green-border': 'var(--green-border)',
        red:            'var(--red)',
        'red-bg':       'var(--red-bg)',
        'red-border':   'var(--red-border)',
        amber:          'var(--amber)',
        'amber-bg':     'var(--amber-bg)',
        'amber-border': 'var(--amber-border)',
      },
      fontFamily: {
        sans: ['Outfit', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      borderRadius: {
        DEFAULT: '12px',
        sm: '6px',
        md: '8px',
        lg: '12px',
        xl: '14px',
        '2xl': '16px',
      },
      boxShadow: {
        DEFAULT: '0 1px 3px rgba(15,23,42,0.06), 0 1px 2px rgba(15,23,42,0.04)',
        lg: '0 8px 30px rgba(15,23,42,0.10)',
        accent: '0 4px 14px var(--accent-shadow)',
      },
      width: {
        sidebar: '225px',
      },
      height: {
        topbar: '60px',
      },
    },
  },
  plugins: [],
}
