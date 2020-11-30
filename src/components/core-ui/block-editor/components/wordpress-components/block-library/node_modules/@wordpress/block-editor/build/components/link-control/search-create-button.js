"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.LinkControlSearchCreate = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var LinkControlSearchCreate = function LinkControlSearchCreate(_ref) {
  var searchTerm = _ref.searchTerm,
      onClick = _ref.onClick,
      itemProps = _ref.itemProps,
      isSelected = _ref.isSelected,
      buttonText = _ref.buttonText;

  if (!searchTerm) {
    return null;
  }

  var text;

  if (buttonText) {
    text = (0, _lodash.isFunction)(buttonText) ? buttonText(searchTerm) : buttonText;
  } else {
    text = (0, _element.createInterpolateElement)((0, _i18n.sprintf)(
    /* translators: %s: search term. */
    (0, _i18n.__)('Create: <mark>%s</mark>'), searchTerm), {
      mark: (0, _element.createElement)("mark", null)
    });
  }

  return (0, _element.createElement)(_components.Button, (0, _extends2.default)({}, itemProps, {
    className: (0, _classnames.default)('block-editor-link-control__search-create block-editor-link-control__search-item', {
      'is-selected': isSelected
    }),
    onClick: onClick
  }), (0, _element.createElement)(_icons.Icon, {
    className: "block-editor-link-control__search-item-icon",
    icon: _icons.plusCircle
  }), (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-header"
  }, (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-title"
  }, text)));
};

exports.LinkControlSearchCreate = LinkControlSearchCreate;
var _default = LinkControlSearchCreate;
exports.default = _default;
//# sourceMappingURL=search-create-button.js.map