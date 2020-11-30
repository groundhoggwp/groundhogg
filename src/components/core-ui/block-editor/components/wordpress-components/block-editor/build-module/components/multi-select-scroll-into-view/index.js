/**
 * External dependencies
 */
import scrollIntoView from 'dom-scroll-into-view';
/**
 * WordPress dependencies
 */

import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { getScrollContainer } from '@wordpress/dom';
/**
 * Internal dependencies
 */

import { getBlockDOMNode } from '../../utils/dom';
/**
 * Scrolls the multi block selection end into view if not in view already. This
 * is important to do after selection by keyboard.
 */

export default function MultiSelectScrollIntoView() {
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

  var _useSelect = useSelect(selector, []),
      isMultiSelection = _useSelect.isMultiSelection,
      selectionEnd = _useSelect.selectionEnd,
      isMultiSelecting = _useSelect.isMultiSelecting;

  useEffect(function () {
    if (!selectionEnd || isMultiSelecting || !isMultiSelection) {
      return;
    }

    var extentNode = getBlockDOMNode(selectionEnd);

    if (!extentNode) {
      return;
    }

    var scrollContainer = getScrollContainer(extentNode); // If there's no scroll container, it follows that there's no scrollbar
    // and thus there's no need to try to scroll into view.

    if (!scrollContainer) {
      return;
    }

    scrollIntoView(extentNode, scrollContainer, {
      onlyScrollIfNeeded: true
    });
  }, [isMultiSelection, selectionEnd, isMultiSelecting]);
  return null;
}
//# sourceMappingURL=index.js.map