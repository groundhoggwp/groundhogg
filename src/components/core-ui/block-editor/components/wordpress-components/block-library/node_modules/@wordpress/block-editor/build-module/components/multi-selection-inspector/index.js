import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';
import { withSelect } from '@wordpress/data';
import { serialize } from '@wordpress/blocks';
import { count as wordCount } from '@wordpress/wordcount';
import { stack } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';

function MultiSelectionInspector(_ref) {
  var blocks = _ref.blocks;
  var words = wordCount(serialize(blocks), 'words');
  return createElement("div", {
    className: "block-editor-multi-selection-inspector__card"
  }, createElement(BlockIcon, {
    icon: stack,
    showColors: true
  }), createElement("div", {
    className: "block-editor-multi-selection-inspector__card-content"
  }, createElement("div", {
    className: "block-editor-multi-selection-inspector__card-title"
  }, sprintf(
  /* translators: %d: number of blocks */
  _n('%d block', '%d blocks', blocks.length), blocks.length)), createElement("div", {
    className: "block-editor-multi-selection-inspector__card-description"
  }, sprintf(
  /* translators: %d: number of words */
  _n('%d word', '%d words', words), words))));
}

export default withSelect(function (select) {
  var _select = select('core/block-editor'),
      getMultiSelectedBlocks = _select.getMultiSelectedBlocks;

  return {
    blocks: getMultiSelectedBlocks()
  };
})(MultiSelectionInspector);
//# sourceMappingURL=index.js.map