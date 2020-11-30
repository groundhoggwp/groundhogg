import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Button, Placeholder } from '@wordpress/components';
import { layout } from '@wordpress/icons';

function BlockVariationPicker(_ref) {
  var _ref$icon = _ref.icon,
      icon = _ref$icon === void 0 ? layout : _ref$icon,
      _ref$label = _ref.label,
      label = _ref$label === void 0 ? __('Choose variation') : _ref$label,
      _ref$instructions = _ref.instructions,
      instructions = _ref$instructions === void 0 ? __('Select a variation to start with.') : _ref$instructions,
      variations = _ref.variations,
      onSelect = _ref.onSelect,
      allowSkip = _ref.allowSkip;
  var classes = classnames('block-editor-block-variation-picker', {
    'has-many-variations': variations.length > 4
  });
  return createElement(Placeholder, {
    icon: icon,
    label: label,
    instructions: instructions,
    className: classes
  }, createElement("ul", {
    className: "block-editor-block-variation-picker__variations",
    role: "list",
    "aria-label": __('Block variations')
  }, variations.map(function (variation) {
    return createElement("li", {
      key: variation.name
    }, createElement(Button, {
      isSecondary: true,
      icon: variation.icon,
      iconSize: 48,
      onClick: function onClick() {
        return onSelect(variation);
      },
      className: "block-editor-block-variation-picker__variation",
      label: variation.description || variation.title
    }), createElement("span", {
      className: "block-editor-block-variation-picker__variation-label",
      role: "presentation"
    }, variation.title));
  })), allowSkip && createElement("div", {
    className: "block-editor-block-variation-picker__skip"
  }, createElement(Button, {
    isLink: true,
    onClick: function onClick() {
      return onSelect();
    }
  }, __('Skip'))));
}

export default BlockVariationPicker;
//# sourceMappingURL=index.js.map