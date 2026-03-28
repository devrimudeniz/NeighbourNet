/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./**/*.php", "./includes/**/*.php", "./admin/**/*.php", "./js/**/*.js"],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                slate: { 900: '#0f172a', 800: '#1e293b' }
            },
            fontFamily: {
                sans: ['Outfit', 'sans-serif'],
            },
            animation: {
                blob: "blob 7s infinite",
            },
            keyframes: {
                blob: {
                    "0%": { transform: "translate(0px, 0px) scale(1)" },
                    "33%": { transform: "translate(30px, -50px) scale(1.1)" },
                    "66%": { transform: "translate(-20px, 20px) scale(0.9)" },
                    "100%": { transform: "translate(0px, 0px) scale(1)" },
                },
            },
        },
    },
    plugins: [],
}
