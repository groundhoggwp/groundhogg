const path = require("path");

const defaultConfig = require("@wordpress/scripts/config/webpack.config");

const rootDir = path.resolve(__dirname);

const paths = {
  srcDir: path.resolve(rootDir, "src"),
  buildDIr: path.resolve(rootDir, "build"),
};

defaultConfig.module.rules.push({
  test: /\.(png|jpe?g|gif)$/i,
  use: [
    {
      loader: "file-loader",
      options: {
        name: "[name].[ext]",
        outputPath: "/img",
        esModule: false,
      },
    },
  ],
});
defaultConfig.module.rules.push({
  test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
  use: [
    {
      loader: "file-loader",
      options: {
        name: "[name].[ext]",
        outputPath: "/fonts",
        esModule: false,
      },
    },
  ],
});

module.exports = {
  ...defaultConfig,
  resolve: {
    ...defaultConfig.resolve,
    // alias directories to paths you can use in import() statements
    alias: {
      components: path.join(paths.srcDir, "components"),
      data: path.join(paths.srcDir, "data"),
      utils: path.join(paths.srcDir, "utils"),
    },
  },
};
