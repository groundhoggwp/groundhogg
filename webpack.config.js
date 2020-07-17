const path = require( 'path' );
const webpack = require( 'webpack' );
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Set different CSS extraction for editor only and common block styles
const funnelCSS = new MiniCssExtractPlugin( {
    filename: './react/funnel/build/build.css',
} );

module.exports = {
    mode: 'development',
    entry: {
        // './blocks/gutenberg/js/blocks' : './blocks/gutenberg/index.js',
        './react/funnel/build/build' : './react/funnel/index.js',
    },
    output: {
        path: path.resolve( __dirname ),
        filename: '[name].js',
    },
    watch: true,
    devtool: 'cheap-eval-source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                },
            },
            {
                test: /\.s?css$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader',
                ]
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: './react/funnel/build/build.css'
        })
    ],
};
