"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = brRemover;

var _utils = require("./utils");

/**
 * Internal dependencies
 */

/**
 * Removes trailing br elements from text-level content.
 *
 * @param {Element} node Node to check.
 */
function brRemover(node) {
  if (node.nodeName !== 'BR') {
    return;
  }

  if ((0, _utils.getSibling)(node, 'next')) {
    return;
  }

  node.parentNode.removeChild(node);
}
//# sourceMappingURL=br-remover.js.map