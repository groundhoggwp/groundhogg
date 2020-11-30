"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkEditor;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _urlInput = _interopRequireDefault(require("../url-input"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LinkEditor(_ref) {
  var autocompleteRef = _ref.autocompleteRef,
      className = _ref.className,
      onChangeInputValue = _ref.onChangeInputValue,
      value = _ref.value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["autocompleteRef", "className", "onChangeInputValue", "value"]);
  return (0, _element.createElement)("form", (0, _extends2.default)({
    className: (0, _classnames.default)('block-editor-url-popover__link-editor', className)
  }, props), (0, _element.createElement)(_urlInput.default, {
    value: value,
    onChange: onChangeInputValue,
    autocompleteRef: autocompleteRef
  }), (0, _element.createElement)(_components.Button, {
    icon: _icons.keyboardReturn,
    label: (0, _i18n.__)('Apply'),
    type: "submit"
  }));
}
//# sourceMappingURL=link-editor.js.map