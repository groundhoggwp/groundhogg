"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Spinner;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function Spinner(props) {
  var progress = props.progress;
  var width = progress + '%';
  return (0, _element.createElement)(_reactNative.View, {
    style: [_style.default.spinner, {
      width: width
    }]
  });
}
//# sourceMappingURL=index.native.js.map