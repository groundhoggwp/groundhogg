"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _transformStyles = _interopRequireDefault(require("../../utils/transform-styles"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function EditorStyles(_ref) {
  var styles = _ref.styles;
  (0, _element.useEffect)(function () {
    var updatedStyles = (0, _transformStyles.default)(styles, '.editor-styles-wrapper');
    var nodes = (0, _lodash.map)((0, _lodash.compact)(updatedStyles), function (updatedCSS) {
      var node = document.createElement('style');
      node.innerHTML = updatedCSS;
      document.body.appendChild(node);
      return node;
    });
    return function () {
      return nodes.forEach(function (node) {
        return document.body.removeChild(node);
      });
    };
  }, [styles]);
  return null;
}

var _default = EditorStyles;
exports.default = _default;
//# sourceMappingURL=index.js.map