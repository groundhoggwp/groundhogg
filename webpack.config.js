const path = require( 'path' );
const webpack = require( 'webpack' );
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: 'development',
    entry: {
        // './blocks/gutenberg/js/blocks' : './blocks/gutenberg/index.js',
        // './react/build/build' : './react/index.js',
        // index : './react/index.js',
        contacts : './react/contacts/index.js',
        funnels : './react/funnels/index.js',
    },
    output: {
        filename: '[name]/bundle.js',
        path: path.resolve( __dirname, 'react/dist' ),
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
            filename: '[name]/bundle.css',
            path: path.resolve( __dirname, 'react/dist' ),
        })
    ],
};
