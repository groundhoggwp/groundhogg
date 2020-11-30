/**
 * WordPress dependencies
 */
import { remove } from '@wordpress/dom';
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

export default function commentRemover(node) {
  if (node.nodeType === COMMENT_NODE) {
    remove(node);
  }
}
//# sourceMappingURL=comment-remover.js.map