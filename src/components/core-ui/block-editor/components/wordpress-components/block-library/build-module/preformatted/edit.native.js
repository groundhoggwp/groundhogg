import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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

import WebPreformattedEdit from './edit.js';
import styles from './styles.scss';

function PreformattedEdit(props) {
  var getStylesFromColorScheme = props.getStylesFromColorScheme;
  var richTextStyle = getStylesFromColorScheme(styles.wpRichTextLight, styles.wpRichTextDark);
  var wpBlockPreformatted = getStylesFromColorScheme(styles.wpBlockPreformattedLight, styles.wpBlockPreformattedDark);

  var propsWithStyle = _objectSpread(_objectSpread({}, props), {}, {
    style: richTextStyle
  });

  return createElement(View, {
    style: wpBlockPreformatted
  }, createElement(WebPreformattedEdit, propsWithStyle));
}

export default withPreferredColorScheme(PreformattedEdit);
//# sourceMappingURL=edit.native.js.map