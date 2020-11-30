import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Gradient, colorsUtils } from '@wordpress/components';
/**
 * Internal dependencies
 */

import styles from './editor.scss';

function ColorBackground(_ref) {
  var children = _ref.children,
      borderRadiusValue = _ref.borderRadiusValue,
      backgroundColor = _ref.backgroundColor;
  var isGradient = colorsUtils.isGradient;
  var wrapperStyles = [styles.richTextWrapper, {
    borderRadius: borderRadiusValue,
    backgroundColor: backgroundColor
  }];
  return createElement(View, {
    style: wrapperStyles
  }, isGradient(backgroundColor) && createElement(Gradient, {
    gradientValue: backgroundColor,
    angleCenter: {
      x: 0.5,
      y: 0.5
    },
    style: [styles.linearGradient, {
      borderRadius: borderRadiusValue
    }]
  }), children);
}

export default ColorBackground;
//# sourceMappingURL=color-background.native.js.map