const path = require( 'path' );
const webpack = require( 'webpack' );
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: 'development',
    entry: {
        // './blocks/gutenberg/js/blocks' : './blocks/gutenberg/index.js',
        // './react/build/build' : './react/index.js',
        index : './react/index.js',
        contacts : './react/contacts/index.js',
    },
    output: {
        path: path.resolve( __dirname, 'react/dist' ),
        filename: '[name].bundle.js',
    },
    optimization: {
        splitChunks:{
            chunks: 'all',
        }
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
            filename: './react/build/build.css'
        })
    ],
};
