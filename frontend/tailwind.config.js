/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        bg: '#F4F4F6',
        surface: '#FFFFFF',
        surface2: '#EEEEF2',
        border: '#DDDDE8',
        border2: '#CACAD8',
        text: '#18182E',
        text2: '#52527A',
        text3: '#9090B4',
        accent: '#3B3BCC',
        'accent-light': '#EBEBFF',
        'accent-mid': '#7B7BE8',
        green: '#1A9E5C',
        'green-bg': '#E6F7EE',
        'green-border': '#A3DFC0',
        red: '#CC3B3B',
        'red-bg': '#FFF0F0',
        'red-border': '#F0AAAA',
        amber: '#B06820',
        'amber-bg': '#FFF5E6',
        'amber-border': '#F0C880',
      },
      fontFamily: {
        sans: ['Outfit', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      borderRadius: {
        DEFAULT: '10px',
        sm: '6px',
        md: '8px',
        lg: '10px',
        xl: '14px',
        '2xl': '16px',
      },
      boxShadow: {
        DEFAULT: '0 2px 12px rgba(24,24,46,0.08)',
        lg: '0 8px 32px rgba(24,24,46,0.13)',
      },
      width: {
        sidebar: '220px',
      },
      height: {
        topbar: '60px',
      },
    },
  },
  plugins: [],
}
