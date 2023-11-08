import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'

export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Jost Variable"', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        primary: {
          DEFAULT: '#B76F2B',
          50: '#ECCCAE',
          100: '#E8C19D',
          200: '#E0AD7C',
          300: '#D8985B',
          400: '#D1833A',
          500: '#B76F2B',
          600: '#8A5320',
          700: '#5C3816',
          800: '#2F1C0B',
          900: '#010100',
          950: '#000000',
        },
        secondary: {
          DEFAULT: '#004C45',
          50: '#F2FFFE',
          100: '#D6FFFB',
          200: '#9EFFF6',
          300: '#66FFF1',
          400: '#2DFFEC',
          500: '#00F4DE',
          600: '#00BCAB',
          700: '#008478',
          800: '#004C45',
          900: '#002320',
          950: '#000F0D',
        },
      },
    },
  },
  plugins: [require('flowbite-typography')],
} satisfies Config
