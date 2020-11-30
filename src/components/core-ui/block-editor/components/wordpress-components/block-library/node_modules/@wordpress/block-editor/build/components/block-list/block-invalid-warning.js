"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockInvalidWarning = BlockInvalidWarning;
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _warning = _interopRequireDefault(require("../warning"));

var _blockCompare = _interopRequireDefault(require("../block-compare"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockInvalidWarning(_ref) {
  var convertToHTML = _ref.convertToHTML,
      convertToBlocks = _ref.convertToBlocks,
      convertToClassic = _ref.convertToClassic,
      attemptBlockRecovery = _ref.attemptBlockRecovery,
      block = _ref.block;
  var hasHTMLBlock = !!(0, _blocks.getBlockType)('core/html');

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      compare = _useState2[0],
      setCompare = _useState2[1];

  var onCompare = (0, _element.useCallback)(function () {
    return setCompare(true);
  }, []);
  var onCompareClose = (0, _element.useCallback)(function () {
    return setCompare(false);
  }, []); // We memo the array here to prevent the children components from being updated unexpectedly

  var hiddenActions = (0, _element.useMemo)(function () {
    return [{
      // translators: Button to fix block content
      title: (0, _i18n._x)('Resolve', 'imperative verb'),
      onClick: onCompare
    }, hasHTMLBlock && {
      title: (0, _i18n.__)('Convert to HTML'),
      onClick: convertToHTML
    }, {
      title: (0, _i18n.__)('Convert to Classic Block'),
      onClick: convertToClassic
    }].filter(Boolean);
  }, [onCompare, convertToHTML, convertToClassic]);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_warning.default, {
    actions: [(0, _element.createElement)(_components.Button, {
      key: "recover",
      onClick: attemptBlockRecovery,
      isPrimary: true
    }, (0, _i18n.__)('Attempt Block Recovery'))],
    secondaryActions: hiddenActions
  }, (0, _i18n.__)('This block contains unexpected or invalid content.')), compare && (0, _element.createElement)(_components.Modal, {
    title: // translators: Dialog title to fix block content
    (0, _i18n.__)('Resolve Block'),
    onRequestClose: onCompareClose,
    className: "block-editor-block-compare"
  }, (0, _element.createElement)(_blockCompare.default, {
    block: block,
    onKeep: convertToHTML,
    onConvert: convertToBlocks,
    convertor: blockToBlocks,
    convertButtonText: (0, _i18n.__)('Convert to Blocks')
  })));
}

var blockToClassic = function blockToClassic(block) {
  return (0, _blocks.createBlock)('core/freeform', {
    content: block.originalContent
  });
};

var blockToHTML = function blockToHTML(block) {
  return (0, _blocks.createBlock)('core/html', {
    content: block.originalContent
  });
};

var blockToBlocks = function blockToBlocks(block) {
  return (0, _blocks.rawHandler)({
    HTML: block.originalContent
  });
};

var recoverBlock = function recoverBlock(_ref2) {
  var name = _ref2.name,
      attributes = _ref2.attributes,
      innerBlocks = _ref2.innerBlocks;
  return (0, _blocks.createBlock)(name, attributes, innerBlocks);
};

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref3) {
  var clientId = _ref3.clientId;
  return {
    block: select('core/block-editor').getBlock(clientId)
  };
}), (0, _data.withDispatch)(function (dispatch, _ref4) {
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

exports.default = _default;
//# sourceMappingURL=block-invalid-warning.js.map