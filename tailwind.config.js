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
            colors: {
                primary: {
                    DEFAULT: '#0D2053',
                    50: '#E8ECF4',
                    100: '#C5CDE4',
                    200: '#8A9BC6',
                    300: '#4F6AA9',
                    400: '#2A4278',
                    500: '#0D2053',
                    600: '#0B1A42',
                    700: '#081332',
                    800: '#050C21',
                    900: '#030611',
                },
                gold: {
                    DEFAULT: '#FFCC00',
                    50: '#FFF9E0',
                    100: '#FFF0A6',
                    200: '#FFE566',
                    300: '#FFDB33',
                    400: '#FFD100',
                    500: '#FFCC00',
                    600: '#E5B800',
                    700: '#B39000',
                    800: '#806600',
                    900: '#4D3D00',
                },
                accent: {
                    DEFAULT: '#E31B23',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms],
};
