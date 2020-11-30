"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BoxControlVisualizer;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _boxControlVisualizerStyles = require("./styles/box-control-visualizer-styles");

var _utils = require("./utils");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BoxControlVisualizer(_ref) {
  var children = _ref.children,
      _ref$showValues = _ref.showValues,
      showValues = _ref$showValues === void 0 ? _utils.DEFAULT_VISUALIZER_VALUES : _ref$showValues,
      _ref$values = _ref.values,
      valuesProp = _ref$values === void 0 ? _utils.DEFAULT_VALUES : _ref$values,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "showValues", "values"]);
  var isPositionAbsolute = !children;
  return (0, _element.createElement)(_boxControlVisualizerStyles.Container, (0, _extends2.default)({}, props, {
    isPositionAbsolute: isPositionAbsolute,
    "aria-hidden": "true"
  }), (0, _element.createElement)(Sides, {
    showValues: showValues,
    values: valuesProp
  }), children);
}

function Sides(_ref2) {
  var _ref2$showValues = _ref2.showValues,
      showValues = _ref2$showValues === void 0 ? _utils.DEFAULT_VISUALIZER_VALUES : _ref2$showValues,
      values = _ref2.values;
  var top = values.top,
      right = values.right,
      bottom = values.bottom,
      left = values.left;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(Top, {
    isVisible: showValues.top,
    value: top
  }), (0, _element.createElement)(Right, {
    isVisible: showValues.right,
    value: right
  }), (0, _element.createElement)(Bottom, {
    isVisible: showValues.bottom,
    value: bottom
  }), (0, _element.createElement)(Left, {
    isVisible: showValues.left,
    value: left
  }));
}

function Top(_ref3) {
  var _ref3$isVisible = _ref3.isVisible,
      isVisible = _ref3$isVisible === void 0 ? false : _ref3$isVisible,
      value = _ref3.value;
  var height = value;
  var animationProps = useSideAnimation(height);
  var isActive = animationProps.isActive || isVisible;
  return (0, _element.createElement)(_boxControlVisualizerStyles.TopView, {
    isActive: isActive,
    style: {
      height: height
    }
  });
}

function Right(_ref4) {
  var _ref4$isVisible = _ref4.isVisible,
      isVisible = _ref4$isVisible === void 0 ? false : _ref4$isVisible,
      value = _ref4.value;
  var width = value;
  var animationProps = useSideAnimation(width);
  var isActive = animationProps.isActive || isVisible;
  return (0, _element.createElement)(_boxControlVisualizerStyles.RightView, {
    isActive: isActive,
    style: {
      width: width
    }
  });
}

function Bottom(_ref5) {
  var _ref5$isVisible = _ref5.isVisible,
      isVisible = _ref5$isVisible === void 0 ? false : _ref5$isVisible,
      value = _ref5.value;
  var height = value;
  var animationProps = useSideAnimation(height);
  var isActive = animationProps.isActive || isVisible;
  return (0, _element.createElement)(_boxControlVisualizerStyles.BottomView, {
    isActive: isActive,
    style: {
      height: height
    }
  });
}

function Left(_ref6) {
  var _ref6$isVisible = _ref6.isVisible,
      isVisible = _ref6$isVisible === void 0 ? false : _ref6$isVisible,
      value = _ref6.value;
  var width = value;
  var animationProps = useSideAnimation(width);
  var isActive = animationProps.isActive || isVisible;
  return (0, _element.createElement)(_boxControlVisualizerStyles.LeftView, {
    isActive: isActive,
    style: {
      width: width
    }
  });
}
/**
 * Custom hook that renders the "flash" animation whenever the value changes.
 *
 * @param {string} value Value of (box) side.
 */


function useSideAnimation(value) {
  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isActive = _useState2[0],
      setIsActive = _useState2[1];

  var valueRef = (0, _element.useRef)(value);
  var timeoutRef = (0, _element.useRef)();

  var clearTimer = function clearTimer() {
    if (timeoutRef.current) {
      window.clearTimeout(timeoutRef.current);
    }
  };

  (0, _element.useEffect)(function () {
    if (value !== valueRef.current) {
      setIsActive(true);
      valueRef.current = value;
      clearTimer();
      timeoutRef.current = setTimeout(function () {
        setIsActive(false);
      }, 400);
    }

    return function () {
      return clearTimer();
    };
  }, [value]);
  return {
    isActive: isActive
  };
}
//# sourceMappingURL=visualizer.js.map