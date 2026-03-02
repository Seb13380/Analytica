import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Cormorant Garamond', 'Georgia', 'serif'],
            },
            colors: {
                // ── Beige palette ──────────────────────────────────────────────
                beige: {
                    50:  '#FDFAF5',
                    100: '#F7F2E8',
                    200: '#EDE4D0',
                    300: '#DDD0B8',
                    400: '#C8B898',
                },
                // ── Charcoal palette ───────────────────────────────────────────
                charcoal: {
                    DEFAULT: '#1C1916',
                    2: '#2E2A25',
                    3: '#5C5449',
                    4: '#8A7E72',
                },
                // ── Gold palette ───────────────────────────────────────────────
                gold: {
                    DEFAULT: '#C9A84C',
                    light: '#E0C278',
                    dark:  '#9B7A2A',
                    pale:  '#F5EDD0',
                },
                // ── Risk level palette (warm, not saturated) ───────────────────
                risk: {
                    critical: '#C0392B',
                    high:     '#C47D1E',
                    medium:   '#B8860B',
                    low:      '#4A7C59',
                    none:     '#5C5449',
                },
            },
            boxShadow: {
                luxury: '0 2px 4px rgba(28,25,22,0.04), 0 8px 20px rgba(28,25,22,0.07), 0 20px 48px rgba(28,25,22,0.05)',
                'luxury-lg': '0 4px 8px rgba(28,25,22,0.06), 0 16px 40px rgba(28,25,22,0.10), 0 32px 64px rgba(28,25,22,0.07)',
                'gold-ring': '0 0 0 3px rgba(201,168,76,0.18)',
            },
            borderRadius: {
                luxury: '2px',
            },
        },
    },

    plugins: [forms],
};
