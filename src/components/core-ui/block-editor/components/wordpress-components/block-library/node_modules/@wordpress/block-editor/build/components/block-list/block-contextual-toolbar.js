"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _navigableToolbar = _interopRequireDefault(require("../navigable-toolbar"));

var _ = require("../");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockContextualToolbar(_ref) {
  var focusOnMount = _ref.focusOnMount,
      props = (0, _objectWithoutProperties2.default)(_ref, ["focusOnMount"]);

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds;

    var _select2 = select('core/blocks'),
        getBlockType = _select2.getBlockType;

    var selectedBlockClientIds = getSelectedBlockClientIds();
    var selectedBlockClientId = selectedBlockClientIds[0];
    return {
      blockType: selectedBlockClientId && getBlockType(getBlockName(selectedBlockClientId))
    };
  }, []),
      blockType = _useSelect.blockType;

  if (blockType) {
    if (!(0, _blocks.hasBlockSupport)(blockType, '__experimentalToolbar', true)) {
      return null;
    }
  }

  return (0, _element.createElement)("div", {
    className: "block-editor-block-contextual-toolbar-wrapper"
  }, (0, _element.createElement)(_navigableToolbar.default, (0, _extends2.default)({
    focusOnMount: focusOnMount,
    className: "block-editor-block-contextual-toolbar"
    /* translators: accessibility text for the block toolbar */
    ,
    "aria-label": (0, _i18n.__)('Block tools')
  }, props), (0, _element.createElement)(_.BlockToolbar, null)));
}

var _default = BlockContextualToolbar;
exports.default = _default;
//# sourceMappingURL=block-contextual-toolbar.js.map