import type { Config } from 'tailwindcss';

export default {
  content: ['./index.html', './src/**/*.{vue,ts,tsx,js,jsx}'],
  theme: {
    extend: {
      colors: {
        primary: '#3498db',
        secondary: '#2c3e50',
        success: '#27ae60',
        danger: '#e74c3c',
        warning: '#f39c12'
      }
    }
  },
  plugins: []
} satisfies Config;
