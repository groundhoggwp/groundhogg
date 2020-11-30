"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _skipToSelectedBlock = _interopRequireDefault(require("../skip-to-selected-block"));

var _blockCard = _interopRequireDefault(require("../block-card"));

var _inspectorControls = _interopRequireDefault(require("../inspector-controls"));

var _inspectorAdvancedControls = _interopRequireDefault(require("../inspector-advanced-controls"));

var _blockStyles = _interopRequireDefault(require("../block-styles"));

var _multiSelectionInspector = _interopRequireDefault(require("../multi-selection-inspector"));

var _defaultStylePicker = _interopRequireDefault(require("../default-style-picker"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockInspector = function BlockInspector(_ref) {
  var blockType = _ref.blockType,
      count = _ref.count,
      hasBlockStyles = _ref.hasBlockStyles,
      selectedBlockClientId = _ref.selectedBlockClientId,
      selectedBlockName = _ref.selectedBlockName,
      _ref$showNoBlockSelec = _ref.showNoBlockSelectedMessage,
      showNoBlockSelectedMessage = _ref$showNoBlockSelec === void 0 ? true : _ref$showNoBlockSelec,
      _ref$bubblesVirtually = _ref.bubblesVirtually,
      bubblesVirtually = _ref$bubblesVirtually === void 0 ? true : _ref$bubblesVirtually;

  if (count > 1) {
    return (0, _element.createElement)("div", {
      className: "block-editor-block-inspector"
    }, (0, _element.createElement)(_multiSelectionInspector.default, null), (0, _element.createElement)(_inspectorControls.default.Slot, {
      bubblesVirtually: bubblesVirtually
    }));
  }

  var isSelectedBlockUnregistered = selectedBlockName === (0, _blocks.getUnregisteredTypeHandlerName)();
  /*
   * If the selected block is of an unregistered type, avoid showing it as an actual selection
   * because we want the user to focus on the unregistered block warning, not block settings.
   */

  if (!blockType || !selectedBlockClientId || isSelectedBlockUnregistered) {
    if (showNoBlockSelectedMessage) {
      return (0, _element.createElement)("span", {
        className: "block-editor-block-inspector__no-blocks"
      }, (0, _i18n.__)('No block selected.'));
    }

    return null;
  }

  return (0, _element.createElement)("div", {
    className: "block-editor-block-inspector"
  }, (0, _element.createElement)(_blockCard.default, {
    blockType: blockType
  }), hasBlockStyles && (0, _element.createElement)("div", null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Styles')
  }, (0, _element.createElement)(_blockStyles.default, {
    clientId: selectedBlockClientId
  }), (0, _blocks.hasBlockSupport)(blockType.name, 'defaultStylePicker', true) && (0, _element.createElement)(_defaultStylePicker.default, {
    blockName: blockType.name
  }))), (0, _element.createElement)(_inspectorControls.default.Slot, {
    bubblesVirtually: bubblesVirtually
  }), (0, _element.createElement)("div", null, (0, _element.createElement)(AdvancedControls, {
    slotName: _inspectorAdvancedControls.default.slotName,
    bubblesVirtually: bubblesVirtually
  })), (0, _element.createElement)(_skipToSelectedBlock.default, {
    key: "back"
  }));
};

var AdvancedControls = function AdvancedControls(_ref2) {
  var slotName = _ref2.slotName,
      bubblesVirtually = _ref2.bubblesVirtually;
  var slot = (0, _components.__experimentalUseSlot)(slotName);
  var hasFills = Boolean(slot.fills && slot.fills.length);

  if (!hasFills) {
    return null;
  }

  return (0, _element.createElement)(_components.PanelBody, {
    className: "block-editor-block-inspector__advanced",
    title: (0, _i18n.__)('Advanced'),
    initialOpen: false
  }, (0, _element.createElement)(_inspectorAdvancedControls.default.Slot, {
    bubblesVirtually: bubblesVirtually
  }));
};

var _default = (0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getSelectedBlockCount = _select.getSelectedBlockCount,
      getBlockName = _select.getBlockName;

  var _select2 = select('core/blocks'),
      getBlockStyles = _select2.getBlockStyles;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockName = selectedBlockClientId && getBlockName(selectedBlockClientId);
  var blockType = selectedBlockClientId && (0, _blocks.getBlockType)(selectedBlockName);
  var blockStyles = selectedBlockClientId && getBlockStyles(selectedBlockName);
  return {
    count: getSelectedBlockCount(),
    hasBlockStyles: blockStyles && blockStyles.length > 0,
    selectedBlockName: selectedBlockName,
    selectedBlockClientId: selectedBlockClientId,
    blockType: blockType
  };
})(BlockInspector);

exports.default = _default;
//# sourceMappingURL=index.js.map