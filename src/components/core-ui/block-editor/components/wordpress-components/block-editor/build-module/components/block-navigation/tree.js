import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalTreeGrid as TreeGrid } from '@wordpress/components';
import { useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockNavigationBranch from './branch';
import { BlockNavigationContext } from './context';
import useBlockNavigationDropZone from './use-block-navigation-drop-zone';
/**
 * Wrap `BlockNavigationRows` with `TreeGrid`. BlockNavigationRows is a
 * recursive component (it renders itself), so this ensures TreeGrid is only
 * present at the very top of the navigation grid.
 *
 * @param {Object} props                        Components props.
 * @param {Object} props.__experimentalFeatures Object used in context provider.
 */

export default function BlockNavigationTree(_ref) {
  var __experimentalFeatures = _ref.__experimentalFeatures,
      props = _objectWithoutProperties(_ref, ["__experimentalFeatures"]);

  var treeGridRef = useRef();
  var blockDropTarget = useBlockNavigationDropZone(treeGridRef);

  if (!__experimentalFeatures) {
    blockDropTarget = undefined;
  }

  var contextValue = useMemo(function () {
    return {
      __experimentalFeatures: __experimentalFeatures,
      blockDropTarget: blockDropTarget
    };
  }, [__experimentalFeatures, blockDropTarget]);
  return createElement(TreeGrid, {
    className: "block-editor-block-navigation-tree",
    "aria-label": __('Block navigation structure'),
    ref: treeGridRef
  }, createElement(BlockNavigationContext.Provider, {
    value: contextValue
  }, createElement(BlockNavigationBranch, props)));
}
//# sourceMappingURL=tree.js.map