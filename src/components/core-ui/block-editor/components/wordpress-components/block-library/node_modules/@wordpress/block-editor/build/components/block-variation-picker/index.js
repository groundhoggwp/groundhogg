"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function BlockVariationPicker(_ref) {
  var _ref$icon = _ref.icon,
      icon = _ref$icon === void 0 ? _icons.layout : _ref$icon,
      _ref$label = _ref.label,
      label = _ref$label === void 0 ? (0, _i18n.__)('Choose variation') : _ref$label,
      _ref$instructions = _ref.instructions,
      instructions = _ref$instructions === void 0 ? (0, _i18n.__)('Select a variation to start with.') : _ref$instructions,
      variations = _ref.variations,
      onSelect = _ref.onSelect,
      allowSkip = _ref.allowSkip;
  var classes = (0, _classnames.default)('block-editor-block-variation-picker', {
    'has-many-variations': variations.length > 4
  });
  return (0, _element.createElement)(_components.Placeholder, {
    icon: icon,
    label: label,
    instructions: instructions,
    className: classes
  }, (0, _element.createElement)("ul", {
    className: "block-editor-block-variation-picker__variations",
    role: "list",
    "aria-label": (0, _i18n.__)('Block variations')
  }, variations.map(function (variation) {
    return (0, _element.createElement)("li", {
      key: variation.name
    }, (0, _element.createElement)(_components.Button, {
      isSecondary: true,
      icon: variation.icon,
      iconSize: 48,
      onClick: function onClick() {
        return onSelect(variation);
      },
      className: "block-editor-block-variation-picker__variation",
      label: variation.description || variation.title
    }), (0, _element.createElement)("span", {
      className: "block-editor-block-variation-picker__variation-label",
      role: "presentation"
    }, variation.title));
  })), allowSkip && (0, _element.createElement)("div", {
    className: "block-editor-block-variation-picker__skip"
  }, (0, _element.createElement)(_components.Button, {
    isLink: true,
    onClick: function onClick() {
      return onSelect();
    }
  }, (0, _i18n.__)('Skip'))));
}

var _default = BlockVariationPicker;
exports.default = _default;
//# sourceMappingURL=index.js.map