import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { get } from 'lodash';
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Icon } from '@wordpress/components';
import { blockDefault } from '@wordpress/icons';
export default function BlockIcon(_ref) {
  var icon = _ref.icon,
      _ref$showColors = _ref.showColors,
      showColors = _ref$showColors === void 0 ? false : _ref$showColors;

  if (get(icon, ['src']) === 'block-default') {
    icon = {
      src: blockDefault
    };
  }

  var renderedIcon = createElement(Icon, {
    icon: icon && icon.src ? icon.src : icon
  });
  var style = showColors ? {
    backgroundColor: icon && icon.background,
    color: icon && icon.foreground
  } : {};
  return createElement(View, {
    style: style
  }, renderedIcon);
}
//# sourceMappingURL=index.native.js.map