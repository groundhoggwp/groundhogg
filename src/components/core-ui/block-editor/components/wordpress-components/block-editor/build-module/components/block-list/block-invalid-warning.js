import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useState, useCallback, useMemo } from '@wordpress/element';
import { getBlockType, createBlock, rawHandler } from '@wordpress/blocks';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import Warning from '../warning';
import BlockCompare from '../block-compare';
export function BlockInvalidWarning(_ref) {
  var convertToHTML = _ref.convertToHTML,
      convertToBlocks = _ref.convertToBlocks,
      convertToClassic = _ref.convertToClassic,
      attemptBlockRecovery = _ref.attemptBlockRecovery,
      block = _ref.block;
  var hasHTMLBlock = !!getBlockType('core/html');

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      compare = _useState2[0],
      setCompare = _useState2[1];

  var onCompare = useCallback(function () {
    return setCompare(true);
  }, []);
  var onCompareClose = useCallback(function () {
    return setCompare(false);
  }, []); // We memo the array here to prevent the children components from being updated unexpectedly

  var hiddenActions = useMemo(function () {
    return [{
      // translators: Button to fix block content
      title: _x('Resolve', 'imperative verb'),
      onClick: onCompare
    }, hasHTMLBlock && {
      title: __('Convert to HTML'),
      onClick: convertToHTML
    }, {
      title: __('Convert to Classic Block'),
      onClick: convertToClassic
    }].filter(Boolean);
  }, [onCompare, convertToHTML, convertToClassic]);
  return createElement(Fragment, null, createElement(Warning, {
    actions: [createElement(Button, {
      key: "recover",
      onClick: attemptBlockRecovery,
      isPrimary: true
    }, __('Attempt Block Recovery'))],
    secondaryActions: hiddenActions
  }, __('This block contains unexpected or invalid content.')), compare && createElement(Modal, {
    title: // translators: Dialog title to fix block content
    __('Resolve Block'),
    onRequestClose: onCompareClose,
    className: "block-editor-block-compare"
  }, createElement(BlockCompare, {
    block: block,
    onKeep: convertToHTML,
    onConvert: convertToBlocks,
    convertor: blockToBlocks,
    convertButtonText: __('Convert to Blocks')
  })));
}

var blockToClassic = function blockToClassic(block) {
  return createBlock('core/freeform', {
    content: block.originalContent
  });
};

var blockToHTML = function blockToHTML(block) {
  return createBlock('core/html', {
    content: block.originalContent
  });
};

var blockToBlocks = function blockToBlocks(block) {
  return rawHandler({
    HTML: block.originalContent
  });
};

var recoverBlock = function recoverBlock(_ref2) {
  var name = _ref2.name,
      attributes = _ref2.attributes,
      innerBlocks = _ref2.innerBlocks;
  return createBlock(name, attributes, innerBlocks);
};

export default compose([withSelect(function (select, _ref3) {
  var clientId = _ref3.clientId;
  return {
    block: select('core/block-editor').getBlock(clientId)
  };
}), withDispatch(function (dispatch, _ref4) {
  var block = _ref4.block;

  var _dispatch = dispatch('core/block-editor'),
      replaceBlock = _dispatch.replaceBlock;

  return {
    convertToClassic: function convertToClassic() {
      replaceBlock(block.clientId, blockToClassic(block));
    },
    convertToHTML: function convertToHTML() {
      replaceBlock(block.clientId, blockToHTML(block));
    },
    convertToBlocks: function convertToBlocks() {
      replaceBlock(block.clientId, blockToBlocks(block));
    },
    attemptBlockRecovery: function attemptBlockRecovery() {
      replaceBlock(block.clientId, recoverBlock(block));
    }
  };
})])(BlockInvalidWarning);
//# sourceMappingURL=block-invalid-warning.js.map