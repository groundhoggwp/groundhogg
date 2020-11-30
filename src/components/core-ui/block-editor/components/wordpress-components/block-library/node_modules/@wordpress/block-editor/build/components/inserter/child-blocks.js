"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ChildBlocks;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ChildBlocks(_ref) {
  var rootClientId = _ref.rootClientId,
      children = _ref.children;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/blocks'),
        getBlockType = _select.getBlockType;

    var _select2 = select('core/block-editor'),
        getBlockName = _select2.getBlockName;

    var rootBlockName = getBlockName(rootClientId);
    var rootBlockType = getBlockType(rootBlockName);
    return {
      rootBlockTitle: rootBlockType && rootBlockType.title,
      rootBlockIcon: rootBlockType && rootBlockType.icon
    };
  }),
      rootBlockTitle = _useSelect.rootBlockTitle,
      rootBlockIcon = _useSelect.rootBlockIcon;

  return (0, _element.createElement)("div", {
    className: "block-editor-inserter__child-blocks"
  }, (rootBlockIcon || rootBlockTitle) && (0, _element.createElement)("div", {
    className: "block-editor-inserter__parent-block-header"
  }, (0, _element.createElement)(_blockIcon.default, {
    icon: rootBlockIcon,
    showColors: true
  }), rootBlockTitle && (0, _element.createElement)("h2", null, rootBlockTitle)), children);
}
//# sourceMappingURL=child-blocks.js.map