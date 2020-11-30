import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { TextControl } from '@wordpress/components';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './actions.scss';
import BottomSeparatorCover from './bottom-separator-cover';

function PanelActions(_ref) {
  var actions = _ref.actions,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  return createElement(View, {
    style: getStylesFromColorScheme(styles.panelActionsContainer, styles.panelActionsContainerDark)
  }, actions.map(function (_ref2) {
    var label = _ref2.label,
        onPress = _ref2.onPress;
    return createElement(TextControl, {
      label: label,
      onPress: onPress,
      labelStyle: styles.defaultLabelStyle,
      key: label
    });
  }), createElement(BottomSeparatorCover, null));
}

export default withPreferredColorScheme(PanelActions);
//# sourceMappingURL=actions.native.js.map