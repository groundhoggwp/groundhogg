"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Label;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _visuallyHidden = _interopRequireDefault(require("../visually-hidden"));

var _inputControlStyles = require("./styles/input-control-styles");

/**
 * Internal dependencies
 */
function Label(_ref) {
  var children = _ref.children,
      hideLabelFromVision = _ref.hideLabelFromVision,
      htmlFor = _ref.htmlFor,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "hideLabelFromVision", "htmlFor"]);
  if (!children) return null;

  if (hideLabelFromVision) {
    return (0, _element.createElement)(_visuallyHidden.default, {
      as: "label",
      htmlFor: htmlFor
    }, children);
  }

  return (0, _element.createElement)(_inputControlStyles.Label, (0, _extends2.default)({
    htmlFor: htmlFor
  }, props), children);
}
//# sourceMappingURL=label.js.map