"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = RovingTabIndex;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _rovingTabIndexContext = require("./roving-tab-index-context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Provider for adding roving tab index behaviors to tree grid structures.
 *
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/components/src/tree-grid/README.md
 *
 * @param {Object}    props          Component props.
 * @param {WPElement} props.children Children to be rendered
 */
function RovingTabIndex(_ref) {
  var children = _ref.children;

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      lastFocusedElement = _useState2[0],
      setLastFocusedElement = _useState2[1]; // Use `useMemo` to avoid creation of a new object for the providerValue
  // on every render. Only create a new object when the `lastFocusedElement`
  // value changes.


  var providerValue = (0, _element.useMemo)(function () {
    return {
      lastFocusedElement: lastFocusedElement,
      setLastFocusedElement: setLastFocusedElement
    };
  }, [lastFocusedElement]);
  return (0, _element.createElement)(_rovingTabIndexContext.RovingTabIndexProvider, {
    value: providerValue
  }, children);
}
//# sourceMappingURL=roving-tab-index.js.map