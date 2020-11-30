import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { Button, Placeholder, ExternalLink } from '@wordpress/components';
import { BlockIcon } from '@wordpress/block-editor';

var EmbedPlaceholder = function EmbedPlaceholder(_ref) {
  var icon = _ref.icon,
      label = _ref.label,
      value = _ref.value,
      onSubmit = _ref.onSubmit,
      onChange = _ref.onChange,
      cannotEmbed = _ref.cannotEmbed,
      fallback = _ref.fallback,
      tryAgain = _ref.tryAgain;
  return createElement(Placeholder, {
    icon: createElement(BlockIcon, {
      icon: icon,
      showColors: true
    }),
    label: label,
    className: "wp-block-embed",
    instructions: __('Paste a link to the content you want to display on your site.')
  }, createElement("form", {
    onSubmit: onSubmit
  }, createElement("input", {
    type: "url",
    value: value || '',
    className: "components-placeholder__input",
    "aria-label": label,
    placeholder: __('Enter URL to embed here…'),
    onChange: onChange
  }), createElement(Button, {
    isPrimary: true,
    type: "submit"
  }, _x('Embed', 'button label'))), createElement("div", {
    className: "components-placeholder__learn-more"
  }, createElement(ExternalLink, {
    href: __('https://wordpress.org/support/article/embeds/')
  }, __('Learn more about embeds'))), cannotEmbed && createElement("div", {
    className: "components-placeholder__error"
  }, createElement("div", {
    className: "components-placeholder__instructions"
  }, __('Sorry, this content could not be embedded.')), createElement(Button, {
    isSecondary: true,
    onClick: tryAgain
  }, _x('Try again', 'button label')), ' ', createElement(Button, {
    isSecondary: true,
    onClick: fallback
  }, _x('Convert to link', 'button label'))));
};

export default EmbedPlaceholder;
//# sourceMappingURL=embed-placeholder.js.map