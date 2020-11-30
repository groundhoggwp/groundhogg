"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockToolbar;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _blockControls = _interopRequireDefault(require("../block-controls"));

var _blockFormatControls = _interopRequireDefault(require("../block-format-controls"));

var _ungroupButton = _interopRequireDefault(require("../ungroup-button"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockToolbar() {
  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockMode = _select.getBlockMode,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
        isBlockValid = _select.isBlockValid;

    var selectedBlockClientIds = getSelectedBlockClientIds();
    return {
      blockClientIds: selectedBlockClientIds,
      isValid: selectedBlockClientIds.length === 1 ? isBlockValid(selectedBlockClientIds[0]) : null,
      mode: selectedBlockClientIds.length === 1 ? getBlockMode(selectedBlockClientIds[0]) : null
    };
  }, []),
      blockClientIds = _useSelect.blockClientIds,
      isValid = _useSelect.isValid,
      mode = _useSelect.mode;

  if (blockClientIds.length === 0) {
    return null;
  }

  return (0, _element.createElement)(_element.Fragment, null, mode === 'visual' && isValid && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_ungroupButton.default, null), (0, _element.createElement)(_blockControls.default.Slot, null), (0, _element.createElement)(_blockFormatControls.default.Slot, null)));
}
//# sourceMappingURL=index.native.js.map