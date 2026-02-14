import { resolve } from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default {
  base: './',
  resolve: {
    alias: {
      '@assets': resolve(__dirname, 'src/assets'),
    },
  },
  plugins: [
    viteStaticCopy({
      targets: [
        {
          src: 'src/assets/icons/*',
          dest: 'image/icons',
        },
        {
          src: 'src/assets/images/payments/*',
          dest: 'image/images/payments',
        },
        {
          src: 'src/assets/images/logo-large.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/logo-large-shadow.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/welder.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/welder-mob.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/welder-with-tank.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/bottle.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/spikelet.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/images/subcategory/barrel.webp',
          dest: 'image',
        },
        {
          src: 'src/assets/videos/explosion.webm',
          dest: 'image',
        },
      ],
    }),
  ],

  build: {
    outDir: './catalog/view',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        theme: resolve(__dirname, 'src/theme-entry.js'),
      },
      output: {
        entryFileNames: 'javascript/[name].js',
        assetFileNames: (assetInfo) => {
          const ext = assetInfo.name?.split('.').pop() || '';
          if (ext === 'css') return 'stylesheet/theme.css';
          if (['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'].includes(ext)) {
            return 'image/[name][extname]';
          }
          if (['woff2', 'woff', 'ttf'].includes(ext)) {
            return 'stylesheet/fonts/[name][extname]';
          }
          return 'other/[name][extname]';
        },
      },
    },
  },
};
