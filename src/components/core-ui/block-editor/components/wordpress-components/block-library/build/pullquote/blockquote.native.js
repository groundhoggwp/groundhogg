"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockQuote = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _blockquote = _interopRequireDefault(require("./blockquote.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockQuote = function BlockQuote(props) {
  var newChildren = _element.Children.map(props.children, function (child) {
    if (child && child.props.identifier === 'value') {
      return (0, _element.cloneElement)(child, {
        style: _blockquote.default.quote
      });
    }

    if (child && child.props.identifier === 'citation') {
      return (0, _element.cloneElement)(child, {
        style: _blockquote.default.citation
      });
    }

    return child;
  });

  return (0, _element.createElement)(_reactNative.View, null, newChildren);
};

exports.BlockQuote = BlockQuote;
//# sourceMappingURL=blockquote.native.js.map