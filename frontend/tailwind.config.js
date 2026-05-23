/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        bg: '#F2F4F6',
        surface: '#FFFFFF',
        surface2: '#EDF0F3',
        border: '#D4DAE2',
        border2: '#BBC5CF',
        text: '#2C3038',
        text2: '#5D5C61',
        text3: '#909AA6',
        accent: '#557A95',
        'accent-light': '#DAE8F0',
        'accent-mid': '#7395AE',
        green: '#2D8E5E',
        'green-bg': '#E7F4EE',
        'green-border': '#A5D5BE',
        red: '#B84444',
        'red-bg': '#F7EDEC',
        'red-border': '#DFA8A8',
        amber: '#9B6417',
        'amber-bg': '#F7F0E4',
        'amber-border': '#D4B47A',
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
        DEFAULT: '0 2px 12px rgba(50,60,75,0.08)',
        lg: '0 8px 32px rgba(50,60,75,0.13)',
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
