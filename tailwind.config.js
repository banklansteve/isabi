import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                ink: '#0B1F3A',
                deep: '#123B72',
                base: {
                    DEFAULT: '#2F6FED', // brand blue (links, accents, chrome)
                    action: '#1A4FB5', // primary actions — between bright base and deep
                    hover: '#154296', // action hover
                },
                tint: '#E3ECFC',
                pale: '#F5F8FE',
                coral: {
                    DEFAULT: '#FF6A3D',
                    deep: '#C94C24',
                    tint: '#FFE4D9',
                },
            },
            fontFamily: {
                display: ['Syne', ...defaultTheme.fontFamily.sans],
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                app: ['Manrope', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                voice: ['"Instrument Serif"', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                'display-xl': [
                    'clamp(2.5rem, 6vw, 4.5rem)',
                    { lineHeight: '1.05', letterSpacing: '-0.03em', fontWeight: '700' },
                ],
                'display-md': [
                    'clamp(1.75rem, 3.5vw, 2.75rem)',
                    { lineHeight: '1.15', letterSpacing: '-0.02em', fontWeight: '700' },
                ],
            },
            transitionDuration: {
                native: '150ms',
            },
            boxShadow: {
                sticky: '0 -8px 32px rgba(11, 31, 58, 0.08)',
                soft: '0 18px 50px rgba(11, 31, 58, 0.14)',
                card: '0 4px 24px rgba(11, 31, 58, 0.06)',
                'card-hover': '0 12px 40px rgba(11, 31, 58, 0.1)',
                premium:
                    '0 1px 2px rgba(11, 31, 58, 0.04), 0 8px 24px rgba(11, 31, 58, 0.06)',
                'premium-hover':
                    '0 4px 8px rgba(11, 31, 58, 0.04), 0 20px 48px rgba(11, 31, 58, 0.12)',
                'premium-ink':
                    '0 1px 2px rgba(11, 31, 58, 0.2), 0 12px 32px rgba(11, 31, 58, 0.28)',
                'premium-ink-hover':
                    '0 8px 16px rgba(11, 31, 58, 0.22), 0 28px 56px rgba(11, 31, 58, 0.34)',
                nav: '0 1px 0 rgba(11, 31, 58, 0.06), 0 8px 28px rgba(11, 31, 58, 0.08)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'soft-in': {
                    '0%': { opacity: '0', transform: 'translateX(10px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 500ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'soft-in': 'soft-in 150ms ease-out both',
            },
        },
    },

    plugins: [forms],
};
