import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { pick } from 'lodash';
/**
 * WordPress dependencies
 */

import { useDispatch, useSelect } from '@wordpress/data';
import { isUnmodifiedDefaultBlock } from '@wordpress/blocks';
import { _n } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
/**
 * @typedef WPInserterConfig
 *
 * @property {string=} rootClientId        Inserter Root Client ID.
 * @property {string=} clientId            Inserter Client ID.
 * @property {boolean} isAppender          Whether the inserter is an appender or not.
 * @property {boolean} selectBlockOnInsert Whether the block should be selected on insert.
 */

/**
 * Returns the insertion point state given the inserter config.
 *
 * @param {WPInserterConfig} config Inserter Config.
 * @return {Array} Insertion Point State (rootClientID, onInsertBlocks and onToggle).
 */

function useInsertionPoint(_ref) {
  var onSelect = _ref.onSelect,
      rootClientId = _ref.rootClientId,
      clientId = _ref.clientId,
      isAppender = _ref.isAppender,
      selectBlockOnInsert = _ref.selectBlockOnInsert;

  var _useSelect = useSelect(function (select) {
    var _getSettings$__experi;

    var _select = select('core/block-editor'),
        getSettings = _select.getSettings,
        getBlockRootClientId = _select.getBlockRootClientId,
        _getBlockSelectionEnd = _select.getBlockSelectionEnd;

    var destRootClientId = rootClientId;

    if (!destRootClientId && !clientId && !isAppender) {
      var end = _getBlockSelectionEnd();

      if (end) {
        destRootClientId = getBlockRootClientId(end);
      }
    }

    return _objectSpread({
      hasPatterns: !!((_getSettings$__experi = getSettings().__experimentalBlockPatterns) === null || _getSettings$__experi === void 0 ? void 0 : _getSettings$__experi.length),
      destinationRootClientId: destRootClientId
    }, pick(select('core/block-editor'), ['getSelectedBlock', 'getBlockIndex', 'getBlockSelectionEnd', 'getBlockOrder']));
  }, [isAppender, clientId, rootClientId]),
      destinationRootClientId = _useSelect.destinationRootClientId,
      getSelectedBlock = _useSelect.getSelectedBlock,
      getBlockIndex = _useSelect.getBlockIndex,
      getBlockSelectionEnd = _useSelect.getBlockSelectionEnd,
      getBlockOrder = _useSelect.getBlockOrder;

  var _useDispatch = useDispatch('core/block-editor'),
      replaceBlocks = _useDispatch.replaceBlocks,
      insertBlocks = _useDispatch.insertBlocks,
      showInsertionPoint = _useDispatch.showInsertionPoint,
      hideInsertionPoint = _useDispatch.hideInsertionPoint;

  function getInsertionIndex() {
    // If the clientId is defined, we insert at the position of the block.
    if (clientId) {
      return getBlockIndex(clientId, destinationRootClientId);
    } // If there a selected block, we insert after the selected block.


    var end = getBlockSelectionEnd();

    if (!isAppender && end) {
      return getBlockIndex(end, destinationRootClientId) + 1;
    } // Otherwise, we insert at the end of the current rootClientId


    return getBlockOrder(destinationRootClientId).length;
  }

  var onInsertBlocks = function onInsertBlocks(blocks, meta) {
    var selectedBlock = getSelectedBlock();

    if (!isAppender && selectedBlock && isUnmodifiedDefaultBlock(selectedBlock)) {
      replaceBlocks(selectedBlock.clientId, blocks, null, null, meta);
    } else {
      insertBlocks(blocks, getInsertionIndex(), destinationRootClientId, selectBlockOnInsert, meta);
    }

    if (!selectBlockOnInsert) {
      // translators: %d: the name of the block that has been added
      var message = _n('%d block added.', '%d blocks added.', blocks.length);

      speak(message);
    }

    if (onSelect) {
      onSelect();
    }
  };

  var onToggleInsertionPoint = function onToggleInsertionPoint(show) {
    if (show) {
      var index = getInsertionIndex();
      showInsertionPoint(destinationRootClientId, index);
    } else {
      hideInsertionPoint();
    }
  };

  return [destinationRootClientId, onInsertBlocks, onToggleInsertionPoint];
}

export default useInsertionPoint;
//# sourceMappingURL=use-insertion-point.js.map