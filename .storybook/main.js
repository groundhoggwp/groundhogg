module.exports = {
  "stories": [
    "../src/components/core-ui/**/*.stories.mdx",
    // Delete this entry later, just want the init in one place
    "../src/components/storybook-example/**/*.stories.@(js|jsx|ts|tsx)",
    "../src/components/core-ui/**/*.stories.@(js|jsx|ts|tsx)"
  ],
  "addons": [
    "@storybook/addon-links",
    "@storybook/addon-essentials",
    "@storybook/preset-create-react-app"
  ]
}
