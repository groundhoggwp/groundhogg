import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { ScrollView } from 'react-native';
/**
 * Internal dependencies
 */

import KeyboardAvoidingView from '../keyboard-avoiding-view';
import styles from './style.android.scss';

var HTMLInputContainer = function HTMLInputContainer(_ref) {
  var children = _ref.children,
      parentHeight = _ref.parentHeight;
  return createElement(KeyboardAvoidingView, {
    style: styles.keyboardAvoidingView,
    parentHeight: parentHeight
  }, createElement(ScrollView, {
    style: styles.scrollView
  }, children));
};

HTMLInputContainer.scrollEnabled = false;
export default HTMLInputContainer;
//# sourceMappingURL=container.android.js.map