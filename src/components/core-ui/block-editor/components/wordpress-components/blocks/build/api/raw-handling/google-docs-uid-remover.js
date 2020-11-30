"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = googleDocsUIdRemover;

var _dom = require("@wordpress/dom");

/**
 * WordPress dependencies
 */
function googleDocsUIdRemover(node) {
  if (!node.id || node.id.indexOf('docs-internal-guid-') !== 0) {
    return;
  }

  (0, _dom.unwrap)(node);
}
//# sourceMappingURL=google-docs-uid-remover.js.map