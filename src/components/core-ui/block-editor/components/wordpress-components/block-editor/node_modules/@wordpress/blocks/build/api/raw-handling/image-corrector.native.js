"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = imageCorrector;

/**
 * This method check for copy pasted img elements to see if they don't have suspicious attributes.
 *
 * @param {Node} node The node to check.
 *
 * @return {void}
 */
function imageCorrector(node) {
  if (node.nodeName !== 'IMG') {
    return;
  } // Remove trackers and hardly visible images.


  if (node.height === 1 || node.width === 1) {
    node.parentNode.removeChild(node);
  }
}
//# sourceMappingURL=image-corrector.native.js.map