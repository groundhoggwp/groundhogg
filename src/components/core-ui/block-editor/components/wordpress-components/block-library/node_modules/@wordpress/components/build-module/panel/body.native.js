import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, View } from 'react-native';
/**
 * Internal dependencies
 */

import styles from './body.scss';
import BottomSeparatorCover from './bottom-separator-cover';
export function PanelBody(_ref) {
  var children = _ref.children,
      title = _ref.title,
      _ref$style = _ref.style,
      style = _ref$style === void 0 ? {} : _ref$style;
  return createElement(View, {
    style: [styles.panelContainer, style]
  }, title && createElement(Text, {
    style: styles.sectionHeaderText
  }, title), children, createElement(BottomSeparatorCover, null));
}
export default PanelBody;
//# sourceMappingURL=body.native.js.map