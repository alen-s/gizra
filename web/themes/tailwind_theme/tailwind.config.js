module.exports = {
  content: [
    "./templates/**/*.twig",
    "./**/*.twig",
    "./**/*.html.twig",
    "./assets/src/js/**/*.js",
    "../../modules/**/*.twig",
    "../../../modules/**/*.twig"
  ],
  theme: {
    extend: {
      extend: {
        screens: {
          '2xl': '1536px',
          '3xl': '1920px',
        },
      },
    },
  },
  plugins: [],
}
