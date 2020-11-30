import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { animated } from 'react-spring/web.cjs';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __experimentalTreeGridRow as TreeGridRow } from '@wordpress/components';
import { useRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import useMovingAnimation from '../use-moving-animation';
var AnimatedTreeGridRow = animated(TreeGridRow);
export default function BlockNavigationLeaf(_ref) {
  var isSelected = _ref.isSelected,
      position = _ref.position,
      level = _ref.level,
      rowCount = _ref.rowCount,
      children = _ref.children,
      className = _ref.className,
      path = _ref.path,
      props = _objectWithoutProperties(_ref, ["isSelected", "position", "level", "rowCount", "children", "className", "path"]);

  var wrapper = useRef(null);
  var adjustScrolling = false;
  var enableAnimation = true;
  var animateOnChange = path.join('_');
  var style = useMovingAnimation(wrapper, isSelected, adjustScrolling, enableAnimation, animateOnChange);
  return createElement(AnimatedTreeGridRow, _extends({
    ref: wrapper,
    style: style,
    className: classnames('block-editor-block-navigation-leaf', className),
    level: level,
    positionInSet: position,
    setSize: rowCount
  }, props), children);
}
//# sourceMappingURL=leaf.js.map