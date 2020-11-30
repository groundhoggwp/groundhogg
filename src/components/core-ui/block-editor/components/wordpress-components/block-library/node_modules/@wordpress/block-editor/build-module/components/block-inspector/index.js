import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { getBlockType, getUnregisteredTypeHandlerName, hasBlockSupport } from '@wordpress/blocks';
import { PanelBody, __experimentalUseSlot as useSlot } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import SkipToSelectedBlock from '../skip-to-selected-block';
import BlockCard from '../block-card';
import InspectorControls from '../inspector-controls';
import InspectorAdvancedControls from '../inspector-advanced-controls';
import BlockStyles from '../block-styles';
import MultiSelectionInspector from '../multi-selection-inspector';
import DefaultStylePicker from '../default-style-picker';

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
    return createElement("div", {
      className: "block-editor-block-inspector"
    }, createElement(MultiSelectionInspector, null), createElement(InspectorControls.Slot, {
      bubblesVirtually: bubblesVirtually
    }));
  }

  var isSelectedBlockUnregistered = selectedBlockName === getUnregisteredTypeHandlerName();
  /*
   * If the selected block is of an unregistered type, avoid showing it as an actual selection
   * because we want the user to focus on the unregistered block warning, not block settings.
   */

  if (!blockType || !selectedBlockClientId || isSelectedBlockUnregistered) {
    if (showNoBlockSelectedMessage) {
      return createElement("span", {
        className: "block-editor-block-inspector__no-blocks"
      }, __('No block selected.'));
    }

    return null;
  }

  return createElement("div", {
    className: "block-editor-block-inspector"
  }, createElement(BlockCard, {
    blockType: blockType
  }), hasBlockStyles && createElement("div", null, createElement(PanelBody, {
    title: __('Styles')
  }, createElement(BlockStyles, {
    clientId: selectedBlockClientId
  }), hasBlockSupport(blockType.name, 'defaultStylePicker', true) && createElement(DefaultStylePicker, {
    blockName: blockType.name
  }))), createElement(InspectorControls.Slot, {
    bubblesVirtually: bubblesVirtually
  }), createElement("div", null, createElement(AdvancedControls, {
    slotName: InspectorAdvancedControls.slotName,
    bubblesVirtually: bubblesVirtually
  })), createElement(SkipToSelectedBlock, {
    key: "back"
  }));
};

var AdvancedControls = function AdvancedControls(_ref2) {
  var slotName = _ref2.slotName,
      bubblesVirtually = _ref2.bubblesVirtually;
  var slot = useSlot(slotName);
  var hasFills = Boolean(slot.fills && slot.fills.length);

  if (!hasFills) {
    return null;
  }

  return createElement(PanelBody, {
    className: "block-editor-block-inspector__advanced",
    title: __('Advanced'),
    initialOpen: false
  }, createElement(InspectorAdvancedControls.Slot, {
    bubblesVirtually: bubblesVirtually
  }));
};

export default withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getSelectedBlockCount = _select.getSelectedBlockCount,
      getBlockName = _select.getBlockName;

  var _select2 = select('core/blocks'),
      getBlockStyles = _select2.getBlockStyles;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockName = selectedBlockClientId && getBlockName(selectedBlockClientId);
  var blockType = selectedBlockClientId && getBlockType(selectedBlockName);
  var blockStyles = selectedBlockClientId && getBlockStyles(selectedBlockName);
  return {
    count: getSelectedBlockCount(),
    hasBlockStyles: blockStyles && blockStyles.length > 0,
    selectedBlockName: selectedBlockName,
    selectedBlockClientId: selectedBlockClientId,
    blockType: blockType
  };
})(BlockInspector);
//# sourceMappingURL=index.js.map