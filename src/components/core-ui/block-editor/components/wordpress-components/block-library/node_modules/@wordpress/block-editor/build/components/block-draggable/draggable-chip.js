"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockDraggableChip;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockDraggableChip(_ref) {
  var clientIds = _ref.clientIds;
  var icon = (0, _data.useSelect)(function (select) {
    var _getBlockType;

    if (clientIds.length !== 1) {
      return;
    }

    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName;

    var _clientIds = (0, _slicedToArray2.default)(clientIds, 1),
        firstId = _clientIds[0];

    var blockName = getBlockName(firstId);
    return (_getBlockType = (0, _blocks.getBlockType)(blockName)) === null || _getBlockType === void 0 ? void 0 : _getBlockType.icon;
  }, [clientIds]);
  return (0, _element.createElement)("div", {
    className: "block-editor-block-draggable-chip-wrapper"
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-draggable-chip"
  }, (0, _element.createElement)(_components.Flex, {
    justify: "center",
    className: "block-editor-block-draggable-chip__content"
  }, (0, _element.createElement)(_components.FlexItem, null, icon ? (0, _element.createElement)(_blockIcon.default, {
    icon: icon
  }) : (0, _i18n.sprintf)(
  /* translators: %d: Number of blocks. */
  (0, _i18n._n)('%d block', '%d blocks', clientIds.length), clientIds.length)), (0, _element.createElement)(_components.FlexItem, null, (0, _element.createElement)(_blockIcon.default, {
    icon: _icons.dragHandle
  })))));
}
//# sourceMappingURL=draggable-chip.js.map