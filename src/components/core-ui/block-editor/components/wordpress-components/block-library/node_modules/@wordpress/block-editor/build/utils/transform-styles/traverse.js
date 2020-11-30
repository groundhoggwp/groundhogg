"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _traverse = _interopRequireDefault(require("traverse"));

var _ast = require("./ast");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function traverseCSS(css, callback) {
  try {
    var parsed = (0, _ast.parse)(css);

    var updated = _traverse.default.map(parsed, function (node) {
      if (!node) {
        return node;
      }

      var updatedNode = callback(node);
      return this.update(updatedNode);
    });

    return (0, _ast.stringify)(updated);
  } catch (err) {
    // eslint-disable-next-line no-console
    console.warn('Error while traversing the CSS: ' + err);
    return null;
  }
}

var _default = traverseCSS;
exports.default = _default;
//# sourceMappingURL=traverse.js.map