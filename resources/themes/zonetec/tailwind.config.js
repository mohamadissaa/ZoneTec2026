module.exports = {
    content: [
        /**
         * This theme's own overridden Blade views.
         */
        "./views/**/*.blade.php",
        "./src/Resources/**/*.js",

        /**
         * The parent (`default`) theme's views. Anything this theme does not
         * override still renders from the Shop package, so its classes must be
         * scanned here or they get purged from the build.
         */
        "../../../packages/Webkul/Shop/src/Resources/**/*.blade.php",
        "../../../packages/Webkul/Shop/src/Resources/**/*.js",
    ],

    theme: {
        container: {
            center: true,
            screens: { "2xl": "1440px" },
            padding: { DEFAULT: "90px" },
        },

        screens: {
            sm: "525px", md: "768px", lg: "1024px", xl: "1240px", "2xl": "1440px",
            1180: "1180px", 1060: "1060px", 991: "991px", 868: "868px",
        },

        extend: {
            colors: {
                /**
                 * Bagisto's five stock colour names are hard-coded across ~100
                 * core Blade files rather than exposed as design tokens, so the
                 * cheapest global recolour is to redefine the names themselves.
                 * Every unoverridden core page picks these up for free.
                 *
                 * `navyBlue` is the de-facto primary: it drives buttons, badges,
                 * active borders and headings, so it takes the brand blue.
                 */
                navyBlue:    "#1754c3",
                lightOrange: "#f2f2f2",
                darkBlue:    "#0043be",
                darkGreen:   "#2e7d32",
                darkPink:    "#e74c3c",

                /**
                 * Explicit tokens for views this theme overrides directly.
                 */
                zonetec: {
                    blue:      "#1754c3",
                    blueDark:  "#0043be",
                    ink:       "#282828",
                    body:      "#505050",
                    muted:     "#a5a5a5",
                    surface:   "#f2f2f2",
                    border:    "#e8e8e8",
                    sale:      "#e74c3c",
                },
            },

            fontFamily: {
                /**
                 * Core templates reference `font-poppins` / `font-dmserif`
                 * directly. Repointing both at Karla restyles the typography
                 * everywhere without touching the parent theme's markup.
                 */
                karla:   ["Karla", "sans-serif"],
                poppins: ["Karla", "sans-serif"],
                dmserif: ["Karla", "sans-serif"],
            },
        },
    },

    plugins: [],

    safelist: [{ pattern: /icon-/ }],
};
