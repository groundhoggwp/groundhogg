module.exports = {
  "stories": [
    "../src/**/*.stories.mdx",
    "../src/components/storybook-full-example/**/*.stories.@(js|jsx|ts|tsx)",
    "../src/components/core-ui/**/*.stories.@(js|jsx|ts|tsx)"
  ],
  "addons": [
    "@storybook/addon-links",
    "@storybook/addon-essentials",
    "@storybook/preset-create-react-app"
  ]
}
