"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

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
var MAX_ITEM_WIDTH = 120;
var HALF_COLUMN = 0.5;

function StylePreview(_ref) {
  var _onPress = _ref.onPress,
      isActive = _ref.isActive,
      style = _ref.style,
      url = _ref.url;

  var _useState = (0, _element.useState)(MAX_ITEM_WIDTH),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      itemWidth = _useState2[0],
      setItemWidth = _useState2[1];

  var label = style.label,
      name = style.name;
  var opacity = (0, _element.useRef)(new _reactNative.Animated.Value(1)).current;

  function onLayout() {
    var columnsNum = // To indicate scroll availabilty, there is a need to display additional half the column
    Math.floor(_components.BottomSheet.getWidth() / MAX_ITEM_WIDTH) + HALF_COLUMN;
    setItemWidth(_components.BottomSheet.getWidth() / columnsNum);
  }

  (0, _element.useEffect)(function () {
    onLayout();

    _reactNative.Dimensions.addEventListener('change', onLayout);

    return function () {
      _reactNative.Dimensions.removeEventListener('change', onLayout);
    };
  }, []);
  var labelStyle = (0, _compose.usePreferredColorSchemeStyle)(_style.default.label, _style.default.labelDark);

  var animateOutline = function animateOutline() {
    opacity.setValue(0);

    _reactNative.Animated.timing(opacity, {
      toValue: 1,
      duration: 100,
      useNativeDriver: true,
      easing: _reactNative.Easing.linear
    }).start();
  };

  var innerOutlineStyle = (0, _compose.usePreferredColorSchemeStyle)(_style.default.innerOutline, _style.default.innerOutlineDark);

  var getOutline = function getOutline(outlineStyles) {
    return outlineStyles.map(function (outlineStyle) {
      return (0, _element.createElement)(_reactNative.Animated.View, {
        style: [outlineStyle, {
          opacity: opacity
        }, _style.default[name]],
        key: outlineStyle
      });
    });
  };

  return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: function onPress() {
      _onPress();

      animateOutline();
    }
  }, (0, _element.createElement)(_reactNative.View, {
    style: [_style.default.container, {
      width: itemWidth
    }]
  }, (0, _element.createElement)(_reactNative.View, {
    style: _style.default.imageWrapper
  }, isActive && getOutline([_style.default.outline, innerOutlineStyle]), (0, _element.createElement)(_reactNative.Image, {
    style: [_style.default.image, _style.default[name]],
    source: {
      uri: url
    }
  })), (0, _element.createElement)(_reactNative.Text, {
    style: [labelStyle, isActive && _style.default.labelSelected]
  }, label)));
}

var _default = StylePreview;
exports.default = _default;
//# sourceMappingURL=preview.native.js.map