"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var Subdirectory = function Subdirectory(_ref) {
  var isRTL = _ref.isRTL,
      extraProps = (0, _objectWithoutProperties2.default)(_ref, ["isRTL"]);
  return (0, _element.createElement)(_components.SVG, (0, _extends2.default)({
    xmlns: "http://www.w3.org/2000/svg",
    width: 14,
    height: 14,
    viewBox: "0 0 20 20"
  }, extraProps), (0, _element.createElement)(_components.Path, {
    d: "M19 15l-6 6-1.42-1.42L15.17 16H4V4h2v10h9.17l-3.59-3.58L13 9l6 6z",
    transform: isRTL ? 'scale(-1,1) translate(-20,0)' : undefined
  }));
};

var _default = Subdirectory;
exports.default = _default;
//# sourceMappingURL=subdirectory-icon.js.map