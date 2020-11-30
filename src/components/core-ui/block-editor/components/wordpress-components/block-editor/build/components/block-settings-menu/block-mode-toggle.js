"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockModeToggle = BlockModeToggle;
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function BlockModeToggle(_ref) {
  var blockType = _ref.blockType,
      mode = _ref.mode,
      onToggleMode = _ref.onToggleMode,
      _ref$small = _ref.small,
      small = _ref$small === void 0 ? false : _ref$small,
      _ref$isCodeEditingEna = _ref.isCodeEditingEnabled,
      isCodeEditingEnabled = _ref$isCodeEditingEna === void 0 ? true : _ref$isCodeEditingEna;

  if (!(0, _blocks.hasBlockSupport)(blockType, 'html', true) || !isCodeEditingEnabled) {
    return null;
  }

  var label = mode === 'visual' ? (0, _i18n.__)('Edit as HTML') : (0, _i18n.__)('Edit visually');
  return (0, _element.createElement)(_components.MenuItem, {
    onClick: onToggleMode
  }, !small && label);
}

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock,
      getBlockMode = _select.getBlockMode,
      getSettings = _select.getSettings;

  var block = getBlock(clientId);
  var isCodeEditingEnabled = getSettings().codeEditingEnabled;
  return {
    mode: getBlockMode(clientId),
    blockType: block ? (0, _blocks.getBlockType)(block.name) : null,
    isCodeEditingEnabled: isCodeEditingEnabled
  };
}), (0, _data.withDispatch)(function (dispatch, _ref3) {
  var _ref3$onToggle = _ref3.onToggle,
      onToggle = _ref3$onToggle === void 0 ? _lodash.noop : _ref3$onToggle,
      clientId = _ref3.clientId;
  return {
    onToggleMode: function onToggleMode() {
      dispatch('core/block-editor').toggleBlockMode(clientId);
      onToggle();
    }
  };
})])(BlockModeToggle);

exports.default = _default;
//# sourceMappingURL=block-mode-toggle.js.map