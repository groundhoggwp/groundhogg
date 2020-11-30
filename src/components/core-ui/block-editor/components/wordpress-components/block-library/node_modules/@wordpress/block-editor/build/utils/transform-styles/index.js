"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _traverse = _interopRequireDefault(require("./traverse"));

var _urlRewrite = _interopRequireDefault(require("./transforms/url-rewrite"));

var _wrap = _interopRequireDefault(require("./transforms/wrap"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Applies a series of CSS rule transforms to wrap selectors inside a given class and/or rewrite URLs depending on the parameters passed.
 *
 * @param {Array} styles CSS rules.
 * @param {string} wrapperClassName Wrapper Class Name.
 * @return {Array} converted rules.
 */
var transformStyles = function transformStyles(styles) {
  var wrapperClassName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  return (0, _lodash.map)(styles, function (_ref) {
    var css = _ref.css,
        baseURL = _ref.baseURL;
    var transforms = [];

    if (wrapperClassName) {
      transforms.push((0, _wrap.default)(wrapperClassName));
    }

    if (baseURL) {
      transforms.push((0, _urlRewrite.default)(baseURL));
    }

    if (transforms.length) {
      return (0, _traverse.default)(css, (0, _compose.compose)(transforms));
    }

    return css;
  });
};

var _default = transformStyles;
exports.default = _default;
//# sourceMappingURL=index.js.map