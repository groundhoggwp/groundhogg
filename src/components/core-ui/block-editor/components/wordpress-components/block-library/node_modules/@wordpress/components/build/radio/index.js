"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _Radio = require("reakit/Radio");

var _button = _interopRequireDefault(require("../button"));

var _radioContext = _interopRequireDefault(require("../radio-context"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Radio(_ref, ref) {
  var children = _ref.children,
      value = _ref.value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "value"]);
  var radioContext = (0, _element.useContext)(_radioContext.default);
  var checked = radioContext.state === value;
  return (0, _element.createElement)(_Radio.Radio, (0, _extends2.default)({
    ref: ref,
    as: _button.default,
    isPrimary: checked,
    isSecondary: !checked,
    value: value
  }, radioContext, props), children || value);
}

var _default = (0, _element.forwardRef)(Radio);

exports.default = _default;
//# sourceMappingURL=index.js.map