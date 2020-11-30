import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/components';
import { withPreferredColorScheme } from '@wordpress/compose';
import { gallery as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
var IconWithColorScheme = withPreferredColorScheme(function (_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var colorSchemeStyles = getStylesFromColorScheme(styles.icon, styles.iconDark);
  return createElement(Icon, _extends({
    icon: icon
  }, colorSchemeStyles));
});
export var sharedIcon = createElement(IconWithColorScheme, null);
//# sourceMappingURL=shared-icon.native.js.map