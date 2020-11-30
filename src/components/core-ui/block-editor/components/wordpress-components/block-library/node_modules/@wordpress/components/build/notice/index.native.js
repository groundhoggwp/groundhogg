"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _blur = require("@react-native-community/blur");

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
var Notice = function Notice(_ref) {
  var onNoticeHidden = _ref.onNoticeHidden,
      content = _ref.content,
      id = _ref.id;

  var _useState = (0, _element.useState)(_reactNative.Dimensions.get('window').width),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      width = _useState2[0],
      setWidth = _useState2[1];

  var _useState3 = (0, _element.useState)(true),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      visible = _useState4[0],
      setVisible = _useState4[1];

  var animationValue = (0, _element.useRef)(new _reactNative.Animated.Value(0)).current;
  var timer = (0, _element.useRef)(null);
  var isIOS = _reactNative.Platform.OS === 'ios';

  var onDimensionsChange = function onDimensionsChange() {
    setWidth(_reactNative.Dimensions.get('window').width);
  };

  (0, _element.useEffect)(function () {
    _reactNative.Dimensions.addEventListener('change', onDimensionsChange);

    return function () {
      _reactNative.Dimensions.removeEventListener('change', onDimensionsChange);
    };
  }, []);
  (0, _element.useEffect)(function () {
    startAnimation();
    return function () {
      clearTimeout(timer === null || timer === void 0 ? void 0 : timer.current);
    };
  }, [visible, id]);

  var onHide = function onHide() {
    setVisible(false);
  };

  var startAnimation = function startAnimation() {
    _reactNative.Animated.timing(animationValue, {
      toValue: visible ? 1 : 0,
      duration: visible ? 300 : 150,
      useNativeDriver: true,
      easing: _reactNative.Easing.out(_reactNative.Easing.quad)
    }).start(function () {
      if (visible && onNoticeHidden) {
        timer.current = setTimeout(function () {
          onHide();
        }, 3000);
      }

      if (!visible && onNoticeHidden) {
        onNoticeHidden(id);
      }
    });
  };

  var noticeSolidStyles = (0, _compose.usePreferredColorSchemeStyle)(_style.default.noticeSolid, _style.default.noticeSolidDark);
  var textStyles = (0, _compose.usePreferredColorSchemeStyle)(_style.default.text, _style.default.textDark);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_reactNative.Animated.View, {
    style: [_style.default.notice, !isIOS && noticeSolidStyles, {
      width: width,
      transform: [{
        translateY: animationValue.interpolate({
          inputRange: [0, 1],
          outputRange: [-24, 0]
        })
      }]
    }]
  }, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: onHide
  }, (0, _element.createElement)(_reactNative.View, {
    style: _style.default.noticeContent
  }, (0, _element.createElement)(_reactNative.Text, {
    style: textStyles
  }, content))), isIOS && (0, _element.createElement)(_blur.BlurView, {
    style: _style.default.blurBackground,
    blurType: "prominent",
    blurAmount: 10
  })));
};

var _default = Notice;
exports.default = _default;
//# sourceMappingURL=index.native.js.map