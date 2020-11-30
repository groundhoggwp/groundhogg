"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _character = require("diff/lib/diff/character");

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _blockView = _interopRequireDefault(require("./block-view"));

/**
 * External dependencies
 */
// diff doesn't tree-shake correctly, so we import from the individual
// module here, to avoid including too much of the library

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockCompare(_ref) {
  var block = _ref.block,
      onKeep = _ref.onKeep,
      onConvert = _ref.onConvert,
      convertor = _ref.convertor,
      convertButtonText = _ref.convertButtonText;

  function getDifference(originalContent, newContent) {
    var difference = (0, _character.diffChars)(originalContent, newContent);
    return difference.map(function (item, pos) {
      var classes = (0, _classnames.default)({
        'block-editor-block-compare__added': item.added,
        'block-editor-block-compare__removed': item.removed
      });
      return (0, _element.createElement)("span", {
        key: pos,
        className: classes
      }, item.value);
    });
  }

  function getConvertedContent(convertedBlock) {
    // The convertor may return an array of items or a single item
    var newBlocks = (0, _lodash.castArray)(convertedBlock); // Get converted block details

    var newContent = newBlocks.map(function (item) {
      return (0, _blocks.getSaveContent)(item.name, item.attributes, item.innerBlocks);
    });
    var renderedContent = newBlocks.map(function (item) {
      return (0, _blocks.getSaveElement)(item.name, item.attributes, item.innerBlocks);
    });
    return {
      rawContent: newContent.join(''),
      renderedContent: renderedContent
    };
  }

  var original = {
    rawContent: block.originalContent,
    renderedContent: (0, _blocks.getSaveElement)(block.name, block.attributes)
  };
  var converted = getConvertedContent(convertor(block));
  var difference = getDifference(original.rawContent, converted.rawContent);
  return (0, _element.createElement)("div", {
    className: "block-editor-block-compare__wrapper"
  }, (0, _element.createElement)(_blockView.default, {
    title: (0, _i18n.__)('Current'),
    className: "block-editor-block-compare__current",
    action: onKeep,
    actionText: (0, _i18n.__)('Convert to HTML'),
    rawContent: original.rawContent,
    renderedContent: original.renderedContent
  }), (0, _element.createElement)(_blockView.default, {
    title: (0, _i18n.__)('After Conversion'),
    className: "block-editor-block-compare__converted",
    action: onConvert,
    actionText: convertButtonText,
    rawContent: difference,
    renderedContent: converted.renderedContent
  }));
}

var _default = BlockCompare;
exports.default = _default;
//# sourceMappingURL=index.js.map