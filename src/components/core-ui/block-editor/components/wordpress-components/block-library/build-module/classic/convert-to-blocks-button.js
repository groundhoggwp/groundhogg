import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarButton } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { rawHandler, serialize } from '@wordpress/blocks';

var ConvertToBlocksButton = function ConvertToBlocksButton(_ref) {
  var clientId = _ref.clientId;

  var _useDispatch = useDispatch('core/block-editor'),
      replaceBlocks = _useDispatch.replaceBlocks;

  var block = useSelect(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]);
  return createElement(ToolbarButton, {
    onClick: function onClick() {
      return replaceBlocks(block.clientId, rawHandler({
        HTML: serialize(block)
      }));
    }
  }, __('Convert to blocks'));
};

export default ConvertToBlocksButton;
//# sourceMappingURL=convert-to-blocks-button.js.map