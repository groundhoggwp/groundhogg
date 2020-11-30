"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockQuotation = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

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
var BlockQuotation = (0, _compose.withPreferredColorScheme)(function (props) {
  var getStylesFromColorScheme = props.getStylesFromColorScheme;
  var blockQuoteStyle = getStylesFromColorScheme(_style.default.wpBlockQuoteLight, _style.default.wpBlockQuoteDark);

  var newChildren = _element.Children.map(props.children, function (child) {
    if (child && child.props.identifier === 'citation') {
      return (0, _element.cloneElement)(child, {
        style: _style.default.wpBlockQuoteCitation
      });
    }

    if (child && child.props.identifier === 'value') {
      return (0, _element.cloneElement)(child, {
        tagsToEliminate: ['div']
      });
    }

    return child;
  });

  return (0, _element.createElement)(_reactNative.View, {
    style: blockQuoteStyle
  }, newChildren);
});
exports.BlockQuotation = BlockQuotation;
//# sourceMappingURL=index.native.js.map