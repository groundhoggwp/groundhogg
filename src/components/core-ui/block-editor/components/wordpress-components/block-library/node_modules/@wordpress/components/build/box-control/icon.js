"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BoxControlIcon;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _boxControlIconStyles = require("./styles/box-control-icon-styles");

/**
 * Internal dependencies
 */
var BASE_ICON_SIZE = 24;

function BoxControlIcon(_ref) {
  var _ref$size = _ref.size,
      size = _ref$size === void 0 ? 24 : _ref$size,
      _ref$side = _ref.side,
      side = _ref$side === void 0 ? 'all' : _ref$side,
      props = (0, _objectWithoutProperties2.default)(_ref, ["size", "side"]);
  var top = getSide(side, 'top');
  var right = getSide(side, 'right');
  var bottom = getSide(side, 'bottom');
  var left = getSide(side, 'left'); // Simulates SVG Icon scaling

  var scale = size / BASE_ICON_SIZE;
  return (0, _element.createElement)(_boxControlIconStyles.Root, (0, _extends2.default)({
    style: {
      transform: "scale(".concat(scale, ")")
    }
  }, props), (0, _element.createElement)(_boxControlIconStyles.Viewbox, null, (0, _element.createElement)(_boxControlIconStyles.TopStroke, {
    isFocused: top
  }), (0, _element.createElement)(_boxControlIconStyles.RightStroke, {
    isFocused: right
  }), (0, _element.createElement)(_boxControlIconStyles.BottomStroke, {
    isFocused: bottom
  }), (0, _element.createElement)(_boxControlIconStyles.LeftStroke, {
    isFocused: left
  })));
}

function getSide(side, value) {
  return side === 'all' || side === value;
}
//# sourceMappingURL=icon.js.map