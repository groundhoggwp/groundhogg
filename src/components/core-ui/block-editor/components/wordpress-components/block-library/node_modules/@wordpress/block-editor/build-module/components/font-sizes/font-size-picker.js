import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { FontSizePicker as BaseFontSizePicker } from '@wordpress/components';
/**
 * Internal dependencies
 */

import useEditorFeature from '../use-editor-feature';

function FontSizePicker(props) {
  var fontSizes = useEditorFeature('typography.fontSizes');
  var disableCustomFontSizes = !useEditorFeature('typography.customFontSize');
  return createElement(BaseFontSizePicker, _extends({}, props, {
    fontSizes: fontSizes,
    disableCustomFontSizes: disableCustomFontSizes
  }));
}

export default FontSizePicker;
//# sourceMappingURL=font-size-picker.js.map