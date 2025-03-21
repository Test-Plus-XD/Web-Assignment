const path = require("path");

module.exports = {
    entry: "./src/js/index.js", // Main file
    output: {
        filename: "bundle.js", // Output file name
        path: path.resolve(__dirname, "dist"), // Output directory
    },
    mode: "development", // Change to "production" for optimized builds
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
};