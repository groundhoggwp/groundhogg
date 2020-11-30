import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
var blocksTab = {
  name: 'blocks',

  /* translators: Blocks tab title in the block inserter. */
  title: __('Blocks')
};
var patternsTab = {
  name: 'patterns',

  /* translators: Patterns tab title in the block inserter. */
  title: __('Patterns')
};
var reusableBlocksTab = {
  name: 'reusable',

  /* translators: Reusable blocks tab title in the block inserter. */
  title: __('Reusable')
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

  return createElement(TabPanel, {
    className: "block-editor-inserter__tabs",
    tabs: tabs,
    onSelect: onSelect
  }, children);
}

export default InserterTabs;
//# sourceMappingURL=tabs.js.map