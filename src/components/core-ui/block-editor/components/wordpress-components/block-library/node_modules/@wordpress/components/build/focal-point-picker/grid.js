"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = FocalPointPickerGrid;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _focalPointPickerStyle = require("./styles/focal-point-picker-style");

var _hooks = require("../utils/hooks");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _window = window,
    clearTimeout = _window.clearTimeout,
    setTimeout = _window.setTimeout;

function FocalPointPickerGrid(_ref) {
  var _ref$bounds = _ref.bounds,
      bounds = _ref$bounds === void 0 ? {} : _ref$bounds,
      value = _ref.value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["bounds", "value"]);
  var animationProps = useRevealAnimation(value);
  var style = {
    width: bounds.width,
    height: bounds.height
  };
  return (0, _element.createElement)(_focalPointPickerStyle.GridView, (0, _extends2.default)({}, props, animationProps, {
    className: "components-focal-point-picker__grid",
    style: style
  }), (0, _element.createElement)(_focalPointPickerStyle.GridLineX, {
    style: {
      top: '33%'
    }
  }), (0, _element.createElement)(_focalPointPickerStyle.GridLineX, {
    style: {
      top: '66%'
    }
  }), (0, _element.createElement)(_focalPointPickerStyle.GridLineY, {
    style: {
      left: '33%'
    }
  }), (0, _element.createElement)(_focalPointPickerStyle.GridLineY, {
    style: {
      left: '66%'
    }
  }));
}
/**
 * Custom hook that renders the "flash" animation whenever the value changes.
 *
 * @param {string} value Value of (box) side.
 */


function useRevealAnimation(value) {
  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isActive = _useState2[0],
      setIsActive = _useState2[1];

  (0, _hooks.useUpdateEffect)(function () {
    setIsActive(true);
    var timeout = setTimeout(function () {
      setIsActive(false);
    }, 600);
    return function () {
      return clearTimeout(timeout);
    };
  }, [value]);
  return {
    isActive: isActive
  };
}
//# sourceMappingURL=grid.js.map