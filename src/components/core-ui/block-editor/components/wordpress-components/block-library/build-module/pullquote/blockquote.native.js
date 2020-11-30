import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Children, cloneElement } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './blockquote.scss';
export var BlockQuote = function BlockQuote(props) {
  var newChildren = Children.map(props.children, function (child) {
    if (child && child.props.identifier === 'value') {
      return cloneElement(child, {
        style: styles.quote
      });
    }

    if (child && child.props.identifier === 'citation') {
      return cloneElement(child, {
        style: styles.citation
      });
    }

    return child;
  });
  return createElement(View, null, newChildren);
};
//# sourceMappingURL=blockquote.native.js.map