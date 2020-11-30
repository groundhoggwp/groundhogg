"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getDragCursor = getDragCursor;
exports.useDragCursor = useDragCursor;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */

/**
 * Gets a CSS cursor value based on a drag direction.
 *
 * @param {string} dragDirection The drag direction.
 * @return {string} The CSS cursor value.
 */
function getDragCursor(dragDirection) {
  var dragCursor = 'ns-resize';

  switch (dragDirection) {
    case 'n':
    case 's':
      dragCursor = 'ns-resize';
      break;

    case 'e':
    case 'w':
      dragCursor = 'ew-resize';
      break;
  }

  return dragCursor;
}
/**
 * Custom hook that renders a drag cursor when dragging.
 *
 * @param {boolean} isDragging The dragging state.
 * @param {string} dragDirection The drag direction.
 *
 * @return {string} The CSS cursor value.
 */


function useDragCursor(isDragging, dragDirection) {
  var dragCursor = getDragCursor(dragDirection);
  (0, _element.useEffect)(function () {
    if (isDragging) {
      document.documentElement.style.cursor = dragCursor;
      document.documentElement.style.pointerEvents = 'none';
    } else {
      document.documentElement.style.cursor = null;
      document.documentElement.style.pointerEvents = null;
    }
  }, [isDragging]);
  return dragCursor;
}
//# sourceMappingURL=utils.js.map