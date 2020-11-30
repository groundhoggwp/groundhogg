"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = iframeRemover;

/**
 * Removes iframes.
 *
 * @param {Node} node The node to check.
 *
 * @return {void}
 */
function iframeRemover(node) {
  if (node.nodeName === 'IFRAME') {
    var text = node.ownerDocument.createTextNode(node.src);
    node.parentNode.replaceChild(text, node);
  }
}
//# sourceMappingURL=iframe-remover.js.map