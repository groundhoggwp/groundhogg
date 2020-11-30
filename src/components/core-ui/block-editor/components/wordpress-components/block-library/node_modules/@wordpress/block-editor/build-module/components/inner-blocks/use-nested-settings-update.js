/**
 * WordPress dependencies
 */
import { useLayoutEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import isShallowEqual from '@wordpress/is-shallow-equal';
/**
 * This hook is a side effect which updates the block-editor store when changes
 * happen to inner block settings. The given props are transformed into a
 * settings object, and if that is different from the current settings object in
 * the block-editor store, then the store is updated with the new settings which
 * came from props.
 *
 * @param {string}   clientId        The client ID of the block to update.
 * @param {string[]} allowedBlocks   An array of block names which are permitted
 *                                   in inner blocks.
 * @param {string}   [templateLock]  The template lock specified for the inner
 *                                   blocks component. (e.g. "all")
 * @param {boolean}  captureToolbars Whether or children toolbars should be shown
 *                                   in the inner blocks component rather than on
 *                                   the child block.
 * @param {string}   orientation     The direction in which the block
 *                                   should face.
 */

export default function useNestedSettingsUpdate(clientId, allowedBlocks, templateLock, captureToolbars, orientation) {
  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockListSettings = _useDispatch.updateBlockListSettings;

  var _useSelect = useSelect(function (select) {
    var rootClientId = select('core/block-editor').getBlockRootClientId(clientId);
    return {
      blockListSettings: select('core/block-editor').getBlockListSettings(clientId),
      parentLock: select('core/block-editor').getTemplateLock(rootClientId)
    };
  }, [clientId]),
      blockListSettings = _useSelect.blockListSettings,
      parentLock = _useSelect.parentLock;

  useLayoutEffect(function () {
    var newSettings = {
      allowedBlocks: allowedBlocks,
      templateLock: templateLock === undefined ? parentLock : templateLock
    }; // These values are not defined for RN, so only include them if they
    // are defined.

    if (captureToolbars !== undefined) {
      newSettings.__experimentalCaptureToolbars = captureToolbars;
    }

    if (orientation !== undefined) {
      newSettings.orientation = orientation;
    }

    if (!isShallowEqual(blockListSettings, newSettings)) {
      updateBlockListSettings(clientId, newSettings);
    }
  }, [clientId, blockListSettings, allowedBlocks, templateLock, parentLock, captureToolbars, orientation, updateBlockListSettings]);
}
//# sourceMappingURL=use-nested-settings-update.js.map