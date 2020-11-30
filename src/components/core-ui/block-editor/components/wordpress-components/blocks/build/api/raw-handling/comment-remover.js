"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = commentRemover;

var _dom = require("@wordpress/dom");

/**
 * WordPress dependencies
 */

/**
 * Browser dependencies
 */
var COMMENT_NODE = window.Node.COMMENT_NODE;
/**
 * Looks for comments, and removes them.
 *
 * @param {Node} node The node to be processed.
 * @return {void}
 */

function commentRemover(node) {
  if (node.nodeType === COMMENT_NODE) {
    (0, _dom.remove)(node);
  }
}
//# sourceMappingURL=comment-remover.js.map