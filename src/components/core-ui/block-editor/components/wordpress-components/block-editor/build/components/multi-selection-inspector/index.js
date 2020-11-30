"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _wordcount = require("@wordpress/wordcount");

var _icons = require("@wordpress/icons");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function MultiSelectionInspector(_ref) {
  var blocks = _ref.blocks;
  var words = (0, _wordcount.count)((0, _blocks.serialize)(blocks), 'words');
  return (0, _element.createElement)("div", {
    className: "block-editor-multi-selection-inspector__card"
  }, (0, _element.createElement)(_blockIcon.default, {
    icon: _icons.stack,
    showColors: true
  }), (0, _element.createElement)("div", {
    className: "block-editor-multi-selection-inspector__card-content"
  }, (0, _element.createElement)("div", {
    className: "block-editor-multi-selection-inspector__card-title"
  }, (0, _i18n.sprintf)(
  /* translators: %d: number of blocks */
  (0, _i18n._n)('%d block', '%d blocks', blocks.length), blocks.length)), (0, _element.createElement)("div", {
    className: "block-editor-multi-selection-inspector__card-description"
  }, (0, _i18n.sprintf)(
  /* translators: %d: number of words */
  (0, _i18n._n)('%d word', '%d words', words), words))));
}

var _default = (0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getMultiSelectedBlocks = _select.getMultiSelectedBlocks;

  return {
    blocks: getMultiSelectedBlocks()
  };
})(MultiSelectionInspector);

exports.default = _default;
//# sourceMappingURL=index.js.map