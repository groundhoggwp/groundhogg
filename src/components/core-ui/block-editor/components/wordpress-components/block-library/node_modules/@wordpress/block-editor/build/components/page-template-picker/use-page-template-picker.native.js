"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.__experimentalUsePageTemplatePickerVisible = exports.__experimentalUsePageTemplatePickerAvailable = void 0;

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

/**
 * WordPress dependencies
 */
var __experimentalUsePageTemplatePickerAvailable = function __experimentalUsePageTemplatePickerAvailable() {
  return (0, _data.useSelect)(function (select) {
    var _select = select('core/editor'),
        getCurrentPostType = _select.getCurrentPostType;

    return getCurrentPostType() === 'page';
  }, []);
};

exports.__experimentalUsePageTemplatePickerAvailable = __experimentalUsePageTemplatePickerAvailable;

var __experimentalUsePageTemplatePickerVisible = function __experimentalUsePageTemplatePickerVisible() {
  var isTemplatePickerAvailable = __experimentalUsePageTemplatePickerAvailable();

  return (0, _data.useSelect)(function (select) {
    var _select2 = select('core/block-editor'),
        getBlockOrder = _select2.getBlockOrder,
        getBlock = _select2.getBlock;

    var blocks = getBlockOrder();
    var isEmptyBlockList = blocks.length === 0;
    var firstBlock = !isEmptyBlockList && getBlock(blocks[0]);
    var isOnlyUnmodifiedDefault = blocks.length === 1 && (0, _blocks.isUnmodifiedDefaultBlock)(firstBlock);
    var isEmptyContent = isEmptyBlockList || isOnlyUnmodifiedDefault;
    return isEmptyContent && isTemplatePickerAvailable;
  }, []);
};

exports.__experimentalUsePageTemplatePickerVisible = __experimentalUsePageTemplatePickerVisible;
//# sourceMappingURL=use-page-template-picker.native.js.map