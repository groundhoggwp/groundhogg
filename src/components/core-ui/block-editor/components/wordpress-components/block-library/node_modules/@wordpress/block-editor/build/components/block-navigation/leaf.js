"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationLeaf;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _web = require("react-spring/web.cjs");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _useMovingAnimation = _interopRequireDefault(require("../use-moving-animation"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var AnimatedTreeGridRow = (0, _web.animated)(_components.__experimentalTreeGridRow);

function BlockNavigationLeaf(_ref) {
  var isSelected = _ref.isSelected,
      position = _ref.position,
      level = _ref.level,
      rowCount = _ref.rowCount,
      children = _ref.children,
      className = _ref.className,
      path = _ref.path,
      props = (0, _objectWithoutProperties2.default)(_ref, ["isSelected", "position", "level", "rowCount", "children", "className", "path"]);
  var wrapper = (0, _element.useRef)(null);
  var adjustScrolling = false;
  var enableAnimation = true;
  var animateOnChange = path.join('_');
  var style = (0, _useMovingAnimation.default)(wrapper, isSelected, adjustScrolling, enableAnimation, animateOnChange);
  return (0, _element.createElement)(AnimatedTreeGridRow, (0, _extends2.default)({
    ref: wrapper,
    style: style,
    className: (0, _classnames.default)('block-editor-block-navigation-leaf', className),
    level: level,
    positionInSet: position,
    setSize: rowCount
  }, props), children);
}
//# sourceMappingURL=leaf.js.map