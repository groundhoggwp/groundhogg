"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = emptyParagraphRemover;

/**
 * Removes empty paragraph elements.
 *
 * @param {Element} node Node to check.
 */
function emptyParagraphRemover(node) {
  if (node.nodeName !== 'P') {
    return;
  }

  if (node.hasChildNodes()) {
    return;
  }

  node.parentNode.removeChild(node);
}
//# sourceMappingURL=empty-paragraph-remover.js.map