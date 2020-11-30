import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import TextareaAutosize from 'react-autosize-textarea';
/**
 * WordPress dependencies
 */

import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { getBlockAttributes, getBlockContent, getBlockType, isValidBlockContent, getSaveContent } from '@wordpress/blocks';

function BlockHTML(_ref) {
  var clientId = _ref.clientId;

  var _useState = useState(''),
      _useState2 = _slicedToArray(_useState, 2),
      html = _useState2[0],
      setHtml = _useState2[1];

  var block = useSelect(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]);

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlock = _useDispatch.updateBlock;

  var onChange = function onChange() {
    var blockType = getBlockType(block.name);
    var attributes = getBlockAttributes(blockType, html, block.attributes); // If html is empty  we reset the block to the default HTML and mark it as valid to avoid triggering an error

    var content = html ? html : getSaveContent(blockType, attributes);
    var isValid = html ? isValidBlockContent(blockType, attributes, content) : true;
    updateBlock(clientId, {
      attributes: attributes,
      originalContent: content,
      isValid: isValid
    }); // Ensure the state is updated if we reset so it displays the default content

    if (!html) {
      setHtml({
        content: content
      });
    }
  };

  useEffect(function () {
    setHtml(getBlockContent(block));
  }, [block]);
  return createElement(TextareaAutosize, {
    className: "block-editor-block-list__block-html-textarea",
    value: html,
    onBlur: onChange,
    onChange: function onChange(event) {
      return setHtml(event.target.value);
    }
  });
}

export default BlockHTML;
//# sourceMappingURL=block-html.js.map