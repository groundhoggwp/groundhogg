/**
 * WordPress dependencies
 */
import { isUnmodifiedDefaultBlock } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
export var __experimentalUsePageTemplatePickerAvailable = function __experimentalUsePageTemplatePickerAvailable() {
  return useSelect(function (select) {
    var _select = select('core/editor'),
        getCurrentPostType = _select.getCurrentPostType;

    return getCurrentPostType() === 'page';
  }, []);
};
export var __experimentalUsePageTemplatePickerVisible = function __experimentalUsePageTemplatePickerVisible() {
  var isTemplatePickerAvailable = __experimentalUsePageTemplatePickerAvailable();

  return useSelect(function (select) {
    var _select2 = select('core/block-editor'),
        getBlockOrder = _select2.getBlockOrder,
        getBlock = _select2.getBlock;

    var blocks = getBlockOrder();
    var isEmptyBlockList = blocks.length === 0;
    var firstBlock = !isEmptyBlockList && getBlock(blocks[0]);
    var isOnlyUnmodifiedDefault = blocks.length === 1 && isUnmodifiedDefaultBlock(firstBlock);
    var isEmptyContent = isEmptyBlockList || isOnlyUnmodifiedDefault;
    return isEmptyContent && isTemplatePickerAvailable;
  }, []);
};
//# sourceMappingURL=use-page-template-picker.native.js.map