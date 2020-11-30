/**
 * WordPress dependencies
 */
import { rawHandler, getBlockContent } from '@wordpress/blocks';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockConvertButton from './block-convert-button';
export default compose(withSelect(function (select, _ref) {
  var clientId = _ref.clientId;
  var block = select('core/block-editor').getBlock(clientId);
  return {
    block: block,
    shouldRender: block && block.name === 'core/html'
  };
}), withDispatch(function (dispatch, _ref2) {
  var block = _ref2.block;
  return {
    onClick: function onClick() {
      return dispatch('core/block-editor').replaceBlocks(block.clientId, rawHandler({
        HTML: getBlockContent(block)
      }));
    }
  };
}))(BlockConvertButton);
//# sourceMappingURL=block-html-convert-button.js.map