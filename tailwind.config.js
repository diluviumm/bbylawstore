/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './app/views/**/*.php',
    './app/*.php',            // palet aksen ada di app/views.php
    './public/assets/js/*.js',
  ],
  safelist: [
    { pattern: /^(bg|from|to)-(pink|rose|red|orange|amber|yellow|emerald|teal|cyan|sky|blue|indigo|purple|fuchsia)-(400|500|600|700|800)$/ },
  ],
  theme: {
    extend: {
      screens: {
        xs: '420px',
      },
      fontFamily: {
        sans: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
      },
    },
  },
  plugins: [require('@tailwindcss/forms')({ strategy: 'base' })],
};
