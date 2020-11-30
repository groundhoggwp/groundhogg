"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationTree;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _branch = _interopRequireDefault(require("./branch"));

var _context = require("./context");

var _useBlockNavigationDropZone = _interopRequireDefault(require("./use-block-navigation-drop-zone"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Wrap `BlockNavigationRows` with `TreeGrid`. BlockNavigationRows is a
 * recursive component (it renders itself), so this ensures TreeGrid is only
 * present at the very top of the navigation grid.
 *
 * @param {Object} props                        Components props.
 * @param {Object} props.__experimentalFeatures Object used in context provider.
 */
function BlockNavigationTree(_ref) {
  var __experimentalFeatures = _ref.__experimentalFeatures,
      props = (0, _objectWithoutProperties2.default)(_ref, ["__experimentalFeatures"]);
  var treeGridRef = (0, _element.useRef)();
  var blockDropTarget = (0, _useBlockNavigationDropZone.default)(treeGridRef);

  if (!__experimentalFeatures) {
    blockDropTarget = undefined;
  }

  var contextValue = (0, _element.useMemo)(function () {
    return {
      __experimentalFeatures: __experimentalFeatures,
      blockDropTarget: blockDropTarget
    };
  }, [__experimentalFeatures, blockDropTarget]);
  return (0, _element.createElement)(_components.__experimentalTreeGrid, {
    className: "block-editor-block-navigation-tree",
    "aria-label": (0, _i18n.__)('Block navigation structure'),
    ref: treeGridRef
  }, (0, _element.createElement)(_context.BlockNavigationContext.Provider, {
    value: contextValue
  }, (0, _element.createElement)(_branch.default, props)));
}
//# sourceMappingURL=tree.js.map