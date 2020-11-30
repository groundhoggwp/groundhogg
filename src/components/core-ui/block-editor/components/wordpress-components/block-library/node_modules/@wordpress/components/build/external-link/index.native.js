"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ExternalLink = ExternalLink;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function ExternalLink(_ref) {
  var href = _ref.href,
      children = _ref.children;
  return (0, _element.createElement)(_reactNative.TouchableOpacity, {
    onPress: function onPress() {
      return _reactNative.Linking.openURL(href);
    },
    accessibilityLabel: (0, _i18n.__)('Open link in a browser')
  }, (0, _element.createElement)(_reactNative.Text, null, children), (0, _element.createElement)(_icons.Icon, {
    icon: _icons.external
  }));
}

var _default = ExternalLink;
exports.default = _default;
//# sourceMappingURL=index.native.js.map