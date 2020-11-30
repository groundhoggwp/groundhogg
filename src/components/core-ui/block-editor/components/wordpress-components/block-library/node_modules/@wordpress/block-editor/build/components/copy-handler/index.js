"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useNotifyCopy = useNotifyCopy;
exports.default = void 0;

var _element = require("@wordpress/element");

var _blocks2 = require("@wordpress/blocks");

var _dom = require("@wordpress/dom");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _getPasteEventData2 = require("../../utils/get-paste-event-data");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useNotifyCopy() {
  var _useSelect = (0, _data.useSelect)(function (select) {
    return select('core/block-editor');
  }, []),
      getBlockName = _useSelect.getBlockName;

  var _useSelect2 = (0, _data.useSelect)(function (select) {
    return select('core/blocks');
  }, []),
      getBlockType = _useSelect2.getBlockType;

  var _useDispatch = (0, _data.useDispatch)('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  return (0, _element.useCallback)(function (eventType, selectedBlockClientIds) {
    var notice = '';

    if (selectedBlockClientIds.length === 1) {
      var clientId = selectedBlockClientIds[0];

      var _getBlockType = getBlockType(getBlockName(clientId)),
          title = _getBlockType.title;

      notice = eventType === 'copy' ? (0, _i18n.sprintf)( // Translators: Name of the block being copied, e.g. "Paragraph".
      (0, _i18n.__)('Copied "%s" to clipboard.'), title) : (0, _i18n.sprintf)( // Translators: Name of the block being cut, e.g. "Paragraph".
      (0, _i18n.__)('Moved "%s" to clipboard.'), title);
    } else {
      notice = eventType === 'copy' ? (0, _i18n.sprintf)( // Translators: %d: Number of blocks being copied.
      (0, _i18n._n)('Copied %d block to clipboard.', 'Copied %d blocks to clipboard.', selectedBlockClientIds.length), selectedBlockClientIds.length) : (0, _i18n.sprintf)( // Translators: %d: Number of blocks being cut.
      (0, _i18n._n)('Moved %d block to clipboard.', 'Moved %d blocks to clipboard.', selectedBlockClientIds.length), selectedBlockClientIds.length);
    }

    createSuccessNotice(notice, {
      type: 'snackbar'
    });
  }, []);
}

function CopyHandler(_ref) {
  var children = _ref.children;
  var containerRef = (0, _element.useRef)();

  var _useSelect3 = (0, _data.useSelect)(function (select) {
    return select('core/block-editor');
  }, []),
      getBlocksByClientId = _useSelect3.getBlocksByClientId,
      getSelectedBlockClientIds = _useSelect3.getSelectedBlockClientIds,
      hasMultiSelection = _useSelect3.hasMultiSelection,
      getSettings = _useSelect3.getSettings;

  var _useDispatch2 = (0, _data.useDispatch)('core/block-editor'),
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

      var hasSelection = event.type === 'copy' || event.type === 'cut' ? (0, _dom.documentHasUncollapsedSelection)(ownerDocument) : (0, _dom.documentHasSelection)(ownerDocument); // Let native copy behaviour take over in input fields.

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
      var serialized = (0, _blocks2.serialize)(blocks);
      event.clipboardData.setData('text/plain', serialized);
      event.clipboardData.setData('text/html', serialized);
    }

    if (event.type === 'cut') {
      removeBlocks(selectedBlockClientIds);
    } else if (event.type === 'paste') {
      var _getPasteEventData = (0, _getPasteEventData2.getPasteEventData)(event),
          plainText = _getPasteEventData.plainText,
          html = _getPasteEventData.html;

      var _blocks = (0, _blocks2.pasteHandler)({
        HTML: html,
        plainText: plainText,
        mode: 'BLOCKS',
        canUserUseUnfilteredHTML: canUserUseUnfilteredHTML
      });

      replaceBlocks(selectedBlockClientIds, _blocks, _blocks.length - 1, -1);
    }
  };

  return (0, _element.createElement)("div", {
    ref: containerRef,
    onCopy: handler,
    onCut: handler,
    onPaste: handler
  }, children);
}

var _default = CopyHandler;
exports.default = _default;
//# sourceMappingURL=index.js.map