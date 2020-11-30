import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useCallback, useRef } from '@wordpress/element';
import { serialize, pasteHandler } from '@wordpress/blocks';
import { documentHasSelection, documentHasUncollapsedSelection } from '@wordpress/dom';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { getPasteEventData } from '../../utils/get-paste-event-data';
export function useNotifyCopy() {
  var _useSelect = useSelect(function (select) {
    return select('core/block-editor');
  }, []),
      getBlockName = _useSelect.getBlockName;

  var _useSelect2 = useSelect(function (select) {
    return select('core/blocks');
  }, []),
      getBlockType = _useSelect2.getBlockType;

  var _useDispatch = useDispatch('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  return useCallback(function (eventType, selectedBlockClientIds) {
    var notice = '';

    if (selectedBlockClientIds.length === 1) {
      var clientId = selectedBlockClientIds[0];

      var _getBlockType = getBlockType(getBlockName(clientId)),
          title = _getBlockType.title;

      notice = eventType === 'copy' ? sprintf( // Translators: Name of the block being copied, e.g. "Paragraph".
      __('Copied "%s" to clipboard.'), title) : sprintf( // Translators: Name of the block being cut, e.g. "Paragraph".
      __('Moved "%s" to clipboard.'), title);
    } else {
      notice = eventType === 'copy' ? sprintf( // Translators: %d: Number of blocks being copied.
      _n('Copied %d block to clipboard.', 'Copied %d blocks to clipboard.', selectedBlockClientIds.length), selectedBlockClientIds.length) : sprintf( // Translators: %d: Number of blocks being cut.
      _n('Moved %d block to clipboard.', 'Moved %d blocks to clipboard.', selectedBlockClientIds.length), selectedBlockClientIds.length);
    }

    createSuccessNotice(notice, {
      type: 'snackbar'
    });
  }, []);
}

function CopyHandler(_ref) {
  var children = _ref.children;
  var containerRef = useRef();

  var _useSelect3 = useSelect(function (select) {
    return select('core/block-editor');
  }, []),
      getBlocksByClientId = _useSelect3.getBlocksByClientId,
      getSelectedBlockClientIds = _useSelect3.getSelectedBlockClientIds,
      hasMultiSelection = _useSelect3.hasMultiSelection,
      getSettings = _useSelect3.getSettings;

  var _useDispatch2 = useDispatch('core/block-editor'),
      flashBlock = _useDispatch2.flashBlock,
      removeBlocks = _useDispatch2.removeBlocks,
      replaceBlocks = _useDispatch2.replaceBlocks;

  var notifyCopy = useNotifyCopy();

  var _getSettings = getSettings(),
      canUserUseUnfilteredHTML = _getSettings.__experimentalCanUserUseUnfilteredHTML;

  var handler = function handler(event) {
    var selectedBlockClientIds = getSelectedBlockClientIds();

    if (selectedBlockClientIds.length === 0) {
      return;
    } // Always handle multiple selected blocks.


    if (!hasMultiSelection()) {
      var target = event.target;
      var ownerDocument = target.ownerDocument; // If copying, only consider actual text selection as selection.
      // Otherwise, any focus on an input field is considered.

      var hasSelection = event.type === 'copy' || event.type === 'cut' ? documentHasUncollapsedSelection(ownerDocument) : documentHasSelection(ownerDocument); // Let native copy behaviour take over in input fields.

      if (hasSelection) {
        return;
      }
    }

    if (!containerRef.current.contains(event.target)) {
      return;
    }

    event.preventDefault();

    if (event.type === 'copy' || event.type === 'cut') {
      if (selectedBlockClientIds.length === 1) {
        flashBlock(selectedBlockClientIds[0]);
      }

      notifyCopy(event.type, selectedBlockClientIds);
      var blocks = getBlocksByClientId(selectedBlockClientIds);
      var serialized = serialize(blocks);
      event.clipboardData.setData('text/plain', serialized);
      event.clipboardData.setData('text/html', serialized);
    }

    if (event.type === 'cut') {
      removeBlocks(selectedBlockClientIds);
    } else if (event.type === 'paste') {
      var _getPasteEventData = getPasteEventData(event),
          plainText = _getPasteEventData.plainText,
          html = _getPasteEventData.html;

      var _blocks = pasteHandler({
        HTML: html,
        plainText: plainText,
        mode: 'BLOCKS',
        canUserUseUnfilteredHTML: canUserUseUnfilteredHTML
      });

      replaceBlocks(selectedBlockClientIds, _blocks, _blocks.length - 1, -1);
    }
  };

  return createElement("div", {
    ref: containerRef,
    onCopy: handler,
    onCut: handler,
    onPaste: handler
  }, children);
}

export default CopyHandler;
//# sourceMappingURL=index.js.map