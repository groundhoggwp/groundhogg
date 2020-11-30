"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useDisplayBlockControls;

var _data = require("@wordpress/data");

var _context = require("../block-edit/context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useDisplayBlockControls() {
  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      isSelected = _useBlockEditContext.isSelected,
      clientId = _useBlockEditContext.clientId,
      name = _useBlockEditContext.name;

  var isFirstAndSameTypeMultiSelected = (0, _data.useSelect)(function (select) {
    // Don't bother checking, see OR statement below.
    if (isSelected) {
      return;
    }

    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        isFirstMultiSelectedBlock = _select.isFirstMultiSelectedBlock,
        getMultiSelectedBlockClientIds = _select.getMultiSelectedBlockClientIds;

    if (!isFirstMultiSelectedBlock(clientId)) {
      return false;
    }

    return getMultiSelectedBlockClientIds().every(function (id) {
      return getBlockName(id) === name;
    });
  }, [clientId, isSelected, name]);
  return isSelected || isFirstAndSameTypeMultiSelected;
}
//# sourceMappingURL=index.js.map