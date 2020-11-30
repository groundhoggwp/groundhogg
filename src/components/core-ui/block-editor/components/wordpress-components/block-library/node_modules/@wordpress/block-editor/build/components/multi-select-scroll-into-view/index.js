"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = MultiSelectScrollIntoView;

var _domScrollIntoView = _interopRequireDefault(require("dom-scroll-into-view"));

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _dom = require("@wordpress/dom");

var _dom2 = require("../../utils/dom");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Scrolls the multi block selection end into view if not in view already. This
 * is important to do after selection by keyboard.
 */
function MultiSelectScrollIntoView() {
  var selector = function selector(select) {
    var _select = select('core/block-editor'),
        getBlockSelectionEnd = _select.getBlockSelectionEnd,
        hasMultiSelection = _select.hasMultiSelection,
        isMultiSelecting = _select.isMultiSelecting;

    return {
      selectionEnd: getBlockSelectionEnd(),
      isMultiSelection: hasMultiSelection(),
      isMultiSelecting: isMultiSelecting()
    };
  };

  var _useSelect = (0, _data.useSelect)(selector, []),
      isMultiSelection = _useSelect.isMultiSelection,
      selectionEnd = _useSelect.selectionEnd,
      isMultiSelecting = _useSelect.isMultiSelecting;

  (0, _element.useEffect)(function () {
    if (!selectionEnd || isMultiSelecting || !isMultiSelection) {
      return;
    }

    var extentNode = (0, _dom2.getBlockDOMNode)(selectionEnd);

    if (!extentNode) {
      return;
    }

    var scrollContainer = (0, _dom.getScrollContainer)(extentNode); // If there's no scroll container, it follows that there's no scrollbar
    // and thus there's no need to try to scroll into view.

    if (!scrollContainer) {
      return;
    }

    (0, _domScrollIntoView.default)(extentNode, scrollContainer, {
      onlyScrollIfNeeded: true
    });
  }, [isMultiSelection, selectionEnd, isMultiSelecting]);
  return null;
}
//# sourceMappingURL=index.js.map