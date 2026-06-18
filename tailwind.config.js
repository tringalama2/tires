import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    50: '#EFF3EE', 100: '#E3E9E2', 200: '#C9D1C8', 300: '#A7B1A6',
                    400: '#7C877B', 500: '#586257', 600: '#3B463B', 700: '#273127',
                    800: '#19211A', 900: '#0F1410', 950: '#0A0D0A',
                },
                blaze: {
                    50: '#FFF2EA', 100: '#FFE2D0', 300: '#FFA266', 400: '#FF7A2E',
                    500: '#FF5400', 600: '#E64A00', 700: '#B83C00',
                },
                steel: {
                    50: '#EFF6FC', 100: '#DCEAF5', 500: '#3B82C4',
                    600: '#2C6BAA', 700: '#1F4E7A',
                },
                fern:  { 100: '#DEF1E5', 600: '#2F8F52' },
                gold:  { 100: '#FBEFC9', 600: '#C98A00' },
                rust:  { 100: '#F8E1DF', 600: '#C42B22' },
                paper: '#FBFCFA',
            },
            fontFamily: {
                display: ['Oswald', 'Arial Narrow', 'sans-serif'],
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            borderRadius: {
                control: '10px',
                card: '14px',
                frame: '20px',
                pill: '999px',
            },
            boxShadow: {
                'tm-sm': '0 1px 2px rgba(10,13,10,0.06), 0 1px 1px rgba(10,13,10,0.04)',
                'tm-md': '0 4px 12px -2px rgba(10,13,10,0.10), 0 2px 4px -2px rgba(10,13,10,0.06)',
                'tm-lg': '0 14px 30px -10px rgba(10,13,10,0.18), 0 4px 8px -4px rgba(10,13,10,0.08)',
            },
            letterSpacing: {
                caps: '0.14em',
                wider2: '0.06em',
            },
            spacing: {
                '112': '28rem',
                '128': '32rem',
                '144': '36rem',
                '156': '40rem',
            },
        },
    },

    plugins: [forms, typography],
};
