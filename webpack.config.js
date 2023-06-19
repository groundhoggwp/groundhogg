var ExtractText = require('extract-text-webpack-plugin');
var debug = process.env.NODE_ENV !== 'production';
var webpack = require('webpack');

var extractEditorSCSS = new ExtractText({
    filename: './blocks.editor.build.css'
});

var extractBlockSCSS = new ExtractText({
    filename: './blocks.style.build.css'
});

var plugins = [extractEditorSCSS, extractBlockSCSS];

var scssConfig = {
    use: [
        {
            loader: 'css-loader'
        },
        {
            loader: 'sass-loader',
            options: {
                outputStyle: 'compressed'
            }
        }
    ]
};

module.exports = {
    context: __dirname,
    // devtool: debug ? 'inline-sourcemap' : null,
    mode: debug ? 'development' : 'production',
    entry: './blocks/gutenberg/src/blocks.js',
    output: {
        path: __dirname + '/blocks/gutenberg/dist/',
        filename: 'blocks.build.js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader'
                    }
                ]
            },
            {
                test: /editor\.scss$/,
                exclude: /node_modules/,
                use: extractEditorSCSS.extract(scssConfig)
            },
            {
                test: /style\.scss$/,
                exclude: /node_modules/,
                use: extractBlockSCSS.extract(scssConfig)
            }
        ]
    },
    plugins: plugins
};
