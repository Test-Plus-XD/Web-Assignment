const {
    sentryWebpackPlugin
} = require("@sentry/webpack-plugin");

const path = require("path");

module.exports = {
    // Main file
    entry: "./src/js/index.js",

    output: {
        filename: "bundle.js", // Output file name
        path: path.resolve(__dirname, "dist"), // Output directory
    },

    // Change to "production" for optimized builds
    mode: "development",

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
                    loader: "babel-loader", // Transpile ES6+ code
                },
            },
        ],
    },

    devtool: "source-map",

    plugins: [sentryWebpackPlugin({
        authToken: process.env.SENTRY_AUTH_TOKEN,
        org: "test-plus",
        project: "js-web-assignment"
    }), sentryWebpackPlugin({
        authToken: process.env.SENTRY_AUTH_TOKEN,
        org: "test-plus",
        project: "php-web-assignment"
    })]
};