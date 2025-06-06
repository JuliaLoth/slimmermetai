const production = process.env.NODE_ENV === 'production';

module.exports = {
  plugins: {
    autoprefixer: {},
    ...(production && {
      cssnano: {
        preset: 'default',
      },
    }),
  },
}; 