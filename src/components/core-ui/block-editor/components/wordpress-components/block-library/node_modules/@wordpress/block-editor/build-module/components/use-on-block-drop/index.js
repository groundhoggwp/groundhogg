/**
 * WordPress dependencies
 */
import { findTransform, getBlockTransforms, pasteHandler } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
/** @typedef {import('@wordpress/element').WPSyntheticEvent} WPSyntheticEvent */

/**
 * Retrieve the data for a block drop event.
 *
 * @param {WPSyntheticEvent} event The drop event.
 *
 * @return {Object} An object with block drag and drop data.
 */

export function parseDropEvent(event) {
  var result = {
    srcRootClientId: null,
    srcClientIds: null,
    srcIndex: null,
    type: null
  };

  if (!event.dataTransfer) {
    return result;
  }

  try {
    result = Object.assign(result, JSON.parse(event.dataTransfer.getData('text')));
  } catch (err) {
    return result;
  }

  return result;
}
/**
 * A function that returns an event handler function for block drop events.
 *
 * @param {string}   targetRootClientId        The root client id where the block(s) will be inserted.
 * @param {number}   targetBlockIndex          The index where the block(s) will be inserted.
 * @param {Function} getBlockIndex             A function that gets the index of a block.
 * @param {Function} getClientIdsOfDescendants A function that gets the client ids of descendant blocks.
 * @param {Function} moveBlocksToPosition      A function that moves blocks.
 *
 * @return {Function} The event handler for a block drop event.
 */

export function onBlockDrop(targetRootClientId, targetBlockIndex, getBlockIndex, getClientIdsOfDescendants, moveBlocksToPosition) {
  return function (event) {
    var _parseDropEvent = parseDropEvent(event),
        sourceRootClientId = _parseDropEvent.srcRootClientId,
        sourceClientIds = _parseDropEvent.srcClientIds,
        dropType = _parseDropEvent.type; // If the user isn't dropping a block, return early.


    if (dropType !== 'block') {
      return;
    }

    var sourceBlockIndex = getBlockIndex(sourceClientIds[0], sourceRootClientId); // If the user is dropping to the same position, return early.

    if (sourceRootClientId === targetRootClientId && sourceBlockIndex === targetBlockIndex) {
      return;
    } // If the user is attempting to drop a block within its own
    // nested blocks, return early as this would create infinite
    // recursion.


    if (sourceClientIds.includes(targetRootClientId) || getClientIdsOfDescendants(sourceClientIds).some(function (id) {
      return id === targetRootClientId;
    })) {
      return;
    }

    var isAtSameLevel = sourceRootClientId === targetRootClientId;
    var draggedBlockCount = sourceClientIds.length; // If the block is kept at the same level and moved downwards,
    // subtract to take into account that the blocks being dragged
    // were removed from the block list above the insertion point.

    var insertIndex = isAtSameLevel && sourceBlockIndex < targetBlockIndex ? targetBlockIndex - draggedBlockCount : targetBlockIndex;
    moveBlocksToPosition(sourceClientIds, sourceRootClientId, targetRootClientId, insertIndex);
  };
}
/**
 * A function that returns an event handler function for block-related file drop events.
 *
 * @param {string}   targetRootClientId    The root client id where the block(s) will be inserted.
 * @param {number}   targetBlockIndex      The index where the block(s) will be inserted.
 * @param {boolean}  hasUploadPermissions  Whether the user has upload permissions.
 * @param {Function} updateBlockAttributes A function that updates a block's attributes.
 * @param {Function} insertBlocks          A function that inserts blocks.
 *
 * @return {Function} The event handler for a block-related file drop event.
 */

export function onFilesDrop(targetRootClientId, targetBlockIndex, hasUploadPermissions, updateBlockAttributes, insertBlocks) {
  return function (files) {
    if (!hasUploadPermissions) {
      return;
    }

    var transformation = findTransform(getBlockTransforms('from'), function (transform) {
      return transform.type === 'files' && transform.isMatch(files);
    });

    if (transformation) {
      var blocks = transformation.transform(files, updateBlockAttributes);
      insertBlocks(blocks, targetBlockIndex, targetRootClientId);
    }
  };
}
/**
 * A function that returns an event handler function for block-related HTML drop events.
 *
 * @param {string}   targetRootClientId The root client id where the block(s) will be inserted.
 * @param {number}   targetBlockIndex   The index where the block(s) will be inserted.
 * @param {Function} insertBlocks       A function that inserts blocks.
 *
 * @return {Function} The event handler for a block-related HTML drop event.
 */

export function onHTMLDrop(targetRootClientId, targetBlockIndex, insertBlocks) {
  return function (HTML) {
    var blocks = pasteHandler({
      HTML: HTML,
      mode: 'BLOCKS'
    });

    if (blocks.length) {
      insertBlocks(blocks, targetBlockIndex, targetRootClientId);
    }
  };
}
/**
 * A React hook for handling block drop events.
 *
 * @param {string} targetRootClientId The root client id where the block(s) will be inserted.
 * @param {number} targetBlockIndex   The index where the block(s) will be inserted.
 *
 * @return {Object} An object that contains the event handlers `onDrop`, `onFilesDrop` and `onHTMLDrop`.
 */

export default function useOnBlockDrop(targetRootClientId, targetBlockIndex) {
  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        _getBlockIndex = _select.getBlockIndex,
        _getClientIdsOfDescendants = _select.getClientIdsOfDescendants,
        getSettings = _select.getSettings;

    return {
      getBlockIndex: _getBlockIndex,
      getClientIdsOfDescendants: _getClientIdsOfDescendants,
      hasUploadPermissions: getSettings().mediaUpload
    };
  }, []),
      getBlockIndex = _useSelect.getBlockIndex,
      getClientIdsOfDescendants = _useSelect.getClientIdsOfDescendants,
      hasUploadPermissions = _useSelect.hasUploadPermissions;

  var _useDispatch = useDispatch('core/block-editor'),
      insertBlocks = _useDispatch.insertBlocks,
      moveBlocksToPosition = _useDispatch.moveBlocksToPosition,
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  return {
    onDrop: onBlockDrop(targetRootClientId, targetBlockIndex, getBlockIndex, getClientIdsOfDescendants, moveBlocksToPosition),
    onFilesDrop: onFilesDrop(targetRootClientId, targetBlockIndex, hasUploadPermissions, updateBlockAttributes, insertBlocks),
    onHTMLDrop: onHTMLDrop(targetRootClientId, targetBlockIndex, insertBlocks)
  };
}
//# sourceMappingURL=index.js.map