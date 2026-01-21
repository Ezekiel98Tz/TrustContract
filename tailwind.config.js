import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    black: '#1a1a1a',
                    gold: '#D4AF37', // Metallic Gold
                    'gold-light': '#F4D06F',
                    'gold-dark': '#B59428',
                    white: '#F9FAFB', // Cool greyish white
                    gray: '#F3F4F6',
                }
            }
        },
    },

    plugins: [forms],
};
