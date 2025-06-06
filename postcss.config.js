const production = process.env.NODE_ENV === 'production';

export default {
  plugins: {
    autoprefixer: {},
    ...(production && {
      cssnano: {
        preset: 'default',
      },
    }),
  },
}; 