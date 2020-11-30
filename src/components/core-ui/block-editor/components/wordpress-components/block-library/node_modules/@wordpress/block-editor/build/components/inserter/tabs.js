"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
var blocksTab = {
  name: 'blocks',

  /* translators: Blocks tab title in the block inserter. */
  title: (0, _i18n.__)('Blocks')
};
var patternsTab = {
  name: 'patterns',

  /* translators: Patterns tab title in the block inserter. */
  title: (0, _i18n.__)('Patterns')
};
var reusableBlocksTab = {
  name: 'reusable',

  /* translators: Reusable blocks tab title in the block inserter. */
  title: (0, _i18n.__)('Reusable')
};

function InserterTabs(_ref) {
  var children = _ref.children,
      _ref$showPatterns = _ref.showPatterns,
      showPatterns = _ref$showPatterns === void 0 ? false : _ref$showPatterns,
      _ref$showReusableBloc = _ref.showReusableBlocks,
      showReusableBlocks = _ref$showReusableBloc === void 0 ? false : _ref$showReusableBloc,
      onSelect = _ref.onSelect;
  var tabs = [blocksTab];

  if (showPatterns) {
    tabs.push(patternsTab);
  }

  if (showReusableBlocks) {
    tabs.push(reusableBlocksTab);
  }

  return (0, _element.createElement)(_components.TabPanel, {
    className: "block-editor-inserter__tabs",
    tabs: tabs,
    onSelect: onSelect
  }, children);
}

var _default = InserterTabs;
exports.default = _default;
//# sourceMappingURL=tabs.js.map