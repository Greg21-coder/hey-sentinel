/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.tsx',
        './resources/**/*.ts',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                primary: '#4f46e5',
                shopify: {
                    50: '#f1f8f5',
                    100: '#d4edda',
                    200: '#a8d8b8',
                    300: '#6bbe92',
                    400: '#29845a',
                    500: '#008060',
                    600: '#006e52',
                    700: '#004c3f',
                    800: '#003d33',
                    900: '#1a1a1a',
                    950: '#0d0d0d',
                },
                surface: {
                    50: '#fafafa',
                    100: '#f6f6f7',
                    200: '#ebebeb',
                    300: '#e1e3e5',
                    400: '#c9cccf',
                    500: '#8c9196',
                    600: '#6d7175',
                    700: '#44474a',
                    800: '#303030',
                    900: '#202223',
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
