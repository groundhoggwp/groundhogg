"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useNestedSettingsUpdate;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _isShallowEqual = _interopRequireDefault(require("@wordpress/is-shallow-equal"));

/**
 * WordPress dependencies
 */

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
function useNestedSettingsUpdate(clientId, allowedBlocks, templateLock, captureToolbars, orientation) {
  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlockListSettings = _useDispatch.updateBlockListSettings;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var rootClientId = select('core/block-editor').getBlockRootClientId(clientId);
    return {
      blockListSettings: select('core/block-editor').getBlockListSettings(clientId),
      parentLock: select('core/block-editor').getTemplateLock(rootClientId)
    };
  }, [clientId]),
      blockListSettings = _useSelect.blockListSettings,
      parentLock = _useSelect.parentLock;

  (0, _element.useLayoutEffect)(function () {
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

    if (!(0, _isShallowEqual.default)(blockListSettings, newSettings)) {
      updateBlockListSettings(clientId, newSettings);
    }
  }, [clientId, blockListSettings, allowedBlocks, templateLock, parentLock, captureToolbars, orientation, updateBlockListSettings]);
}
//# sourceMappingURL=use-nested-settings-update.js.map