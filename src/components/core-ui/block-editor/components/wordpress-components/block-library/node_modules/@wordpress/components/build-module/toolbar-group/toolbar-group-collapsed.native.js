import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import DropdownMenu from '../dropdown-menu';
import styles from './style.scss';

function ToolbarGroupCollapsed(_ref) {
  var _ref$controls = _ref.controls,
      controls = _ref$controls === void 0 ? [] : _ref$controls,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      passedStyle = _ref.passedStyle,
      props = _objectWithoutProperties(_ref, ["controls", "getStylesFromColorScheme", "passedStyle"]);

  return createElement(View, {
    style: [getStylesFromColorScheme(styles.container, styles.containerDark), passedStyle]
  }, createElement(DropdownMenu, _extends({
    controls: controls
  }, props)));
}

export default withPreferredColorScheme(ToolbarGroupCollapsed);
//# sourceMappingURL=toolbar-group-collapsed.native.js.map