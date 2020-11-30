"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var Tooltip = function Tooltip(_ref) {
  var onTooltipHidden = _ref.onTooltipHidden;

  var _useState = (0, _element.useState)(true),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      visible = _useState2[0],
      setVisible = _useState2[1];

  var animationValue = (0, _element.useRef)(new _reactNative.Animated.Value(0)).current;
  (0, _element.useEffect)(function () {
    startAnimation();
  }, [visible]);

  var onHide = function onHide() {
    setVisible(false);
  };

  var startAnimation = function startAnimation() {
    _reactNative.Animated.timing(animationValue, {
      toValue: visible ? 1 : 0,
      duration: visible ? 300 : 150,
      useNativeDriver: true,
      delay: visible ? 500 : 0,
      easing: _reactNative.Easing.out(_reactNative.Easing.quad)
    }).start(function () {
      if (!visible && onTooltipHidden) {
        onTooltipHidden();
      }
    });
  };

  var stylesOverlay = [_style.default.overlay, {
    height: _reactNative.Dimensions.get('window').height
  }];
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: onHide
  }, (0, _element.createElement)(_reactNative.View, {
    style: stylesOverlay
  })), (0, _element.createElement)(_reactNative.Animated.View, {
    style: {
      opacity: animationValue,
      transform: [{
        translateY: animationValue.interpolate({
          inputRange: [0, 1],
          outputRange: [visible ? 4 : -8, -8]
        })
      }]
    }
  }, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: onHide
  }, (0, _element.createElement)(_reactNative.View, {
    style: [_style.default.tooltip, {
      shadowColor: _style.default.tooltipShadow.color,
      shadowOffset: {
        width: 0,
        height: 2
      },
      shadowOpacity: 0.25,
      shadowRadius: 2,
      elevation: 2
    }]
  }, (0, _element.createElement)(_reactNative.Text, {
    style: _style.default.text
  }, (0, _i18n.__)('Try a starter layout')), (0, _element.createElement)(_reactNative.View, {
    style: _style.default.arrow
  })))));
};

var _default = Tooltip;
exports.default = _default;
//# sourceMappingURL=index.native.js.map