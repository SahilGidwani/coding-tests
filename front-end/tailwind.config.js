/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{html,js}"],
  theme: {
    extend: {
      fontFamily: {
        sans: ["Open Sans", "sans-serif"],
      },
      colors: {
        primary: "#00838F",
        "primary-dark": "#046c75",
        secondary: "#2C2C2C",
        grey: "#54566a",
        blue: "#0069CE",
        orange: "#FF8853",
        teal: "#0AB0BF",
      },
      fontSize: {
        h1: ["40px", { lineHeight: "48px", fontWeight: "400" }],
        h2: ["32px", { lineHeight: "48px", fontWeight: "400" }],
        h3: ["24px", { lineHeight: "32px", fontWeight: "600" }],
        h4: ["20px", { lineHeight: "28px", fontWeight: "600" }],
        body: ["16px", { lineHeight: "28px" }],
        link: ["16px", { lineHeight: "28px", fontWeight: "600" }],
      },
      fontWeight: {
        normal: 400,
        semibold: 600,
      },
    },
    container: {
      // Changing container size only for 2xl causes to overide other breakpoints as well.
      screens: {
        sm: "640px",
        md: "768px",
        lg: "1024px",
        xl: "1280px",
        "2xl": "1280px",
      },
      padding: {
        DEFAULT: "2.5rem",
        xl: "3.75rem",
      },
    },
  },
  plugins: [],
};
