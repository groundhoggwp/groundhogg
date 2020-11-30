import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './editor.scss';

function ColumnsPreview(_ref) {
  var columnWidths = _ref.columnWidths,
      selectedColumnIndex = _ref.selectedColumnIndex;
  var columnsPreviewStyle = usePreferredColorSchemeStyle(styles.columnsPreview, styles.columnsPreviewDark);
  var columnIndicatorStyle = usePreferredColorSchemeStyle(styles.columnIndicator, styles.columnIndicatorDark);
  return createElement(View, {
    style: columnsPreviewStyle
  }, columnWidths.map(function (width, index) {
    var isSelectedColumn = index === selectedColumnIndex;
    return createElement(View, {
      style: [isSelectedColumn && columnIndicatorStyle, {
        flex: width
      }],
      key: index
    });
  }));
}

export default ColumnsPreview;
//# sourceMappingURL=column-preview.native.js.map