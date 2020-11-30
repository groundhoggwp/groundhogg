"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.performLayoutAnimation = performLayoutAnimation;

var _reactNative = require("react-native");

/**
 * External dependencies
 */
var ANIMATION_DURATION = 300;

function performLayoutAnimation() {
  var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : ANIMATION_DURATION;

  _reactNative.LayoutAnimation.configureNext(_reactNative.LayoutAnimation.create(duration, _reactNative.LayoutAnimation.Types.easeInEaseOut, _reactNative.LayoutAnimation.Properties.opacity));
}
//# sourceMappingURL=index.native.js.map