import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { castArray } from 'lodash'; // diff doesn't tree-shake correctly, so we import from the individual
// module here, to avoid including too much of the library

import { diffChars } from 'diff/lib/diff/character';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { getSaveContent, getSaveElement } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import BlockView from './block-view';

function BlockCompare(_ref) {
  var block = _ref.block,
      onKeep = _ref.onKeep,
      onConvert = _ref.onConvert,
      convertor = _ref.convertor,
      convertButtonText = _ref.convertButtonText;

  function getDifference(originalContent, newContent) {
    var difference = diffChars(originalContent, newContent);
    return difference.map(function (item, pos) {
      var classes = classnames({
        'block-editor-block-compare__added': item.added,
        'block-editor-block-compare__removed': item.removed
      });
      return createElement("span", {
        key: pos,
        className: classes
      }, item.value);
    });
  }

  function getConvertedContent(convertedBlock) {
    // The convertor may return an array of items or a single item
    var newBlocks = castArray(convertedBlock); // Get converted block details

    var newContent = newBlocks.map(function (item) {
      return getSaveContent(item.name, item.attributes, item.innerBlocks);
    });
    var renderedContent = newBlocks.map(function (item) {
      return getSaveElement(item.name, item.attributes, item.innerBlocks);
    });
    return {
      rawContent: newContent.join(''),
      renderedContent: renderedContent
    };
  }

  var original = {
    rawContent: block.originalContent,
    renderedContent: getSaveElement(block.name, block.attributes)
  };
  var converted = getConvertedContent(convertor(block));
  var difference = getDifference(original.rawContent, converted.rawContent);
  return createElement("div", {
    className: "block-editor-block-compare__wrapper"
  }, createElement(BlockView, {
    title: __('Current'),
    className: "block-editor-block-compare__current",
    action: onKeep,
    actionText: __('Convert to HTML'),
    rawContent: original.rawContent,
    renderedContent: original.renderedContent
  }), createElement(BlockView, {
    title: __('After Conversion'),
    className: "block-editor-block-compare__converted",
    action: onConvert,
    actionText: convertButtonText,
    rawContent: difference,
    renderedContent: converted.renderedContent
  }));
}

export default BlockCompare;
//# sourceMappingURL=index.js.map