"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = blockquoteNormaliser;

var _normaliseBlocks = _interopRequireDefault(require("./normalise-blocks"));

/**
 * Internal dependencies
 */
function blockquoteNormaliser(node) {
  if (node.nodeName !== 'BLOCKQUOTE') {
    return;
  }

  node.innerHTML = (0, _normaliseBlocks.default)(node.innerHTML);
}
//# sourceMappingURL=blockquote-normaliser.js.map