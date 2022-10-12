module.exports = function (api) {

  api.cache(false)

  return {
    presets: [
      [
        "@babel/preset-react",
        {
          "pragma": "wp.element.createElement"
        }
      ],
      "minify",
      "@babel/env"
    ]
  };
}
