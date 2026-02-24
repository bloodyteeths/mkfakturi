import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    resolve: {
        alias: {
            "vue-i18n": "vue-i18n/dist/vue-i18n.cjs.js",
            '@': resolve(__dirname, './resources/'),
            $fonts: resolve(__dirname, './resources/static/fonts'),
            $images: resolve(__dirname, './resources/static/img')
        },
        extensions: ['.js', '.ts', '.jsx', '.tsx', '.json', '.vue', '.mjs']
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', 'vue-router', 'pinia', 'vue-i18n'],
                    'vendor-charts': ['chart.js', 'd3'],
                    'vendor-editor': [
                        '@tiptap/core',
                        '@tiptap/starter-kit',
                        '@tiptap/vue-3',
                        '@tiptap/extension-link',
                        '@tiptap/extension-text-align',
                    ],
                    'vendor-utils': ['lodash', 'moment', 'axios'],
                    'vendor-ui': [
                        '@headlessui/vue',
                        '@heroicons/vue',
                        'v-tooltip',
                        'v-money3',
                        'vue-flatpickr-component',
                    ],
                    'vendor-validation': [
                        '@vuelidate/core',
                        '@vuelidate/components',
                        '@vuelidate/validators',
                    ],
                },
            },
        },
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    // The Vue plugin will re-write asset URLs, when referenced
                    // in Single File Components, to point to the Laravel web
                    // server. Setting this to `null` allows the Laravel plugin
                    // to instead re-write asset URLs to point to the Vite
                    // server instead.
                    base: null,

                    // The Vue plugin will parse absolute URLs and treat them
                    // as absolute paths to files on disk. Setting this to
                    // `false` will leave absolute URLs un-touched so they can
                    // reference assets in the public directory as expected.
                    includeAbsolute: false,
                },
            },
        }),
        laravel([
            'resources/scripts/main.js'
        ])
    ]
});
