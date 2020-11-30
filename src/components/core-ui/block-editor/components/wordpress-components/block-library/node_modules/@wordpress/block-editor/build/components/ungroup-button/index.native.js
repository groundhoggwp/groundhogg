"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.UngroupButton = UngroupButton;
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _icon = _interopRequireDefault(require("./icon"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function UngroupButton(_ref) {
  var onConvertFromGroup = _ref.onConvertFromGroup,
      _ref$isUngroupable = _ref.isUngroupable,
      isUngroupable = _ref$isUngroupable === void 0 ? false : _ref$isUngroupable;

  if (!isUngroupable) {
    return null;
  }

  return (0, _element.createElement)(_components.Toolbar, null, (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Ungroup'),
    icon: _icon.default,
    onClick: onConvertFromGroup
  }));
}

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlock = _select.getBlock;

  var _select2 = select('core/blocks'),
      getGroupingBlockName = _select2.getGroupingBlockName;

  var selectedId = getSelectedBlockClientId();
  var selectedBlock = getBlock(selectedId);
  var groupingBlockName = getGroupingBlockName();
  var isUngroupable = selectedBlock && selectedBlock.innerBlocks && !!selectedBlock.innerBlocks.length && selectedBlock.name === groupingBlockName;
  var innerBlocks = isUngroupable ? selectedBlock.innerBlocks : [];
  return {
    isUngroupable: isUngroupable,
    clientId: selectedId,
    innerBlocks: innerBlocks
  };
}), (0, _data.withDispatch)(function (dispatch, _ref2) {
  var clientId = _ref2.clientId,
      innerBlocks = _ref2.innerBlocks,
      _ref2$onToggle = _ref2.onToggle,
      onToggle = _ref2$onToggle === void 0 ? _lodash.noop : _ref2$onToggle;

  var _dispatch = dispatch('core/block-editor'),
      replaceBlocks = _dispatch.replaceBlocks;

  return {
    onConvertFromGroup: function onConvertFromGroup() {
      if (!innerBlocks.length) {
        return;
      }

      replaceBlocks(clientId, innerBlocks);
      onToggle();
    }
  };
})])(UngroupButton);

exports.default = _default;
//# sourceMappingURL=index.native.js.map