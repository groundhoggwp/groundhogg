import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { ScrollView } from 'react-native';
/**
 * Internal dependencies
 */

import styles from './styles.scss';

var Container = function Container(_ref) {
  var style = _ref.style,
      children = _ref.children;
  return createElement(ScrollView, {
    alwaysBounceHorizontal: false,
    contentContainerStyle: styles.content,
    horizontal: true,
    keyboardShouldPersistTaps: "always",
    showsHorizontalScrollIndicator: false,
    style: [styles.container, style]
  }, children);
};

export default Container;
//# sourceMappingURL=container.native.js.map