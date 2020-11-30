"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _richText = _interopRequireDefault(require("../rich-text"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var EditableText = (0, _element.forwardRef)(function (props, ref) {
  return (0, _element.createElement)(_richText.default, (0, _extends2.default)({
    ref: ref
  }, props, {
    __unstableDisableFormats: true,
    preserveWhiteSpace: true
  }));
});

EditableText.Content = function (_ref) {
  var _ref$value = _ref.value,
      value = _ref$value === void 0 ? '' : _ref$value,
      _ref$tagName = _ref.tagName,
      Tag = _ref$tagName === void 0 ? 'div' : _ref$tagName,
      props = (0, _objectWithoutProperties2.default)(_ref, ["value", "tagName"]);
  return (0, _element.createElement)(Tag, props, value);
};
/**
 * Renders an editable text input in which text formatting is not allowed.
 */


var _default = EditableText;
exports.default = _default;
//# sourceMappingURL=index.js.map