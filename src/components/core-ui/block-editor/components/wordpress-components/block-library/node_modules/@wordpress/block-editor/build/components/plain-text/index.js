"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactAutosizeTextarea = _interopRequireDefault(require("react-autosize-textarea"));

var _classnames = _interopRequireDefault(require("classnames"));

var _editableText = _interopRequireDefault(require("../editable-text"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/plain-text/README.md
 */
var PlainText = (0, _element.forwardRef)(function (_ref, ref) {
  var __experimentalVersion = _ref.__experimentalVersion,
      props = (0, _objectWithoutProperties2.default)(_ref, ["__experimentalVersion"]);

  if (__experimentalVersion === 2) {
    return (0, _element.createElement)(_editableText.default, (0, _extends2.default)({
      ref: ref
    }, props));
  }

  var className = props.className,
      _onChange = props.onChange,
      remainingProps = (0, _objectWithoutProperties2.default)(props, ["className", "onChange"]);
  return (0, _element.createElement)(_reactAutosizeTextarea.default, (0, _extends2.default)({
    ref: ref,
    className: (0, _classnames.default)('block-editor-plain-text', className),
    onChange: function onChange(event) {
      return _onChange(event.target.value);
    }
  }, remainingProps));
});
var _default = PlainText;
exports.default = _default;
//# sourceMappingURL=index.js.map