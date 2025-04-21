import { sentryWebpackPlugin } from '@sentry/webpack-plugin';
import { fileURLToPath } from 'url';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default {
    // Main file
    entry: './src/js/index.js',

    output: {
        filename: 'bundle.js', // Output file name
        path: path.resolve(__dirname, 'dist') // Output  __dirname directory
    },

    // Change to "production" for optimized builds
    mode: 'development',

    experiments: {
        topLevelAwait: true // Enable top-level await (for Firebase modules)
    },

    resolve: {
        fullySpecified: false // Fixes issues with Firebase ESM imports
    },

    module: {
        rules: [
            {
                test: /\.js$/, // Apply this rule to JS files
                exclude: /node_modules/, // Ignore dependencies
                use: {
                    loader: 'babel-loader', // Transpile ES6+ code
                },
            },
        ],
    },

    devtool: 'source-map',

    plugins: [
        sentryWebpackPlugin({ // Use it as a function call
            authToken: process.env.SENTRY_AUTH_TOKEN,
            org: 'test-plus',
            project: 'js-web-assignment'
        }),
        sentryWebpackPlugin({ // Use it as a function call
            authToken: process.env.SENTRY_AUTH_TOKEN,
            org: 'test-plus',
            project: 'php-web-assignment'
        })
    ]
};