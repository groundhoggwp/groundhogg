"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = FocalPoint;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _focalPointStyle = require("./styles/focal-point-style");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function FocalPoint(_ref) {
  var _ref$coordinates = _ref.coordinates,
      coordinates = _ref$coordinates === void 0 ? {
    left: '50%',
    top: '50%'
  } : _ref$coordinates,
      _ref$isDragging = _ref.isDragging,
      isDragging = _ref$isDragging === void 0 ? false : _ref$isDragging,
      props = (0, _objectWithoutProperties2.default)(_ref, ["coordinates", "isDragging"]);
  var classes = (0, _classnames.default)('components-focal-point-picker__icon_container', isDragging && 'is-dragging');
  var style = {
    left: coordinates.left,
    top: coordinates.top
  };
  return (0, _element.createElement)(_focalPointStyle.FocalPointWrapper, (0, _extends2.default)({}, props, {
    className: classes,
    style: style
  }), (0, _element.createElement)(_focalPointStyle.PointerIconSVG, {
    className: "components-focal-point-picker__icon",
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 30 30"
  }, (0, _element.createElement)(_focalPointStyle.PointerIconPathOutline, {
    className: "components-focal-point-picker__icon-outline",
    d: "M15 1C7.3 1 1 7.3 1 15s6.3 14 14 14 14-6.3 14-14S22.7 1 15 1zm0 22c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"
  }), (0, _element.createElement)(_focalPointStyle.PointerIconPathFill, {
    className: "components-focal-point-picker__icon-fill",
    d: "M15 3C8.4 3 3 8.4 3 15s5.4 12 12 12 12-5.4 12-12S21.6 3 15 3zm0 22C9.5 25 5 20.5 5 15S9.5 5 15 5s10 4.5 10 10-4.5 10-10 10z"
  })));
}
//# sourceMappingURL=focal-point.js.map