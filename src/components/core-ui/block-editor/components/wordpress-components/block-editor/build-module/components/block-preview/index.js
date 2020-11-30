import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { castArray } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { memo, useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import BlockEditorProvider from '../provider';
import LiveBlockPreview from './live';
import AutoHeightBlockPreview from './auto';
export function BlockPreview(_ref) {
  var blocks = _ref.blocks,
      _ref$__experimentalPa = _ref.__experimentalPadding,
      __experimentalPadding = _ref$__experimentalPa === void 0 ? 0 : _ref$__experimentalPa,
      _ref$viewportWidth = _ref.viewportWidth,
      viewportWidth = _ref$viewportWidth === void 0 ? 700 : _ref$viewportWidth,
      _ref$__experimentalLi = _ref.__experimentalLive,
      __experimentalLive = _ref$__experimentalLi === void 0 ? false : _ref$__experimentalLi,
      __experimentalOnClick = _ref.__experimentalOnClick;

  var settings = useSelect(function (select) {
    return select('core/block-editor').getSettings();
  }, []);
  var renderedBlocks = useMemo(function () {
    return castArray(blocks);
  }, [blocks]);

  if (!blocks || blocks.length === 0) {
    return null;
  }

  return createElement(BlockEditorProvider, {
    value: renderedBlocks,
    settings: settings
  }, __experimentalLive ? createElement(LiveBlockPreview, {
    onClick: __experimentalOnClick
  }) : createElement(AutoHeightBlockPreview, {
    viewportWidth: viewportWidth,
    __experimentalPadding: __experimentalPadding
  }));
}
/**
 * BlockPreview renders a preview of a block or array of blocks.
 *
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-preview/README.md
 *
 * @param {Object} preview options for how the preview should be shown
 * @param {Array|Object} preview.blocks A block instance (object) or an array of blocks to be previewed.
 * @param {number} preview.viewportWidth Width of the preview container in pixels. Controls at what size the blocks will be rendered inside the preview. Default: 700.
 *
 * @return {WPComponent} The component to be rendered.
 */

export default memo(BlockPreview);
//# sourceMappingURL=index.js.map