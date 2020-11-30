import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { get } from 'lodash';
/**
 * WordPress dependencies
 */

import { Icon } from '@wordpress/components';
import { blockDefault } from '@wordpress/icons';
export default function BlockIcon(_ref) {
  var icon = _ref.icon,
      _ref$showColors = _ref.showColors,
      showColors = _ref$showColors === void 0 ? false : _ref$showColors,
      className = _ref.className;

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
  return createElement("span", {
    style: style,
    className: classnames('block-editor-block-icon', className, {
      'has-colors': showColors
    })
  }, renderedIcon);
}
//# sourceMappingURL=index.js.map