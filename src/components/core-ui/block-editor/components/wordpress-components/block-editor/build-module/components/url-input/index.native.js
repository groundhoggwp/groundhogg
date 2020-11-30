import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { TextInput } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
export default function URLInput(_ref) {
  var _ref$value = _ref.value,
      value = _ref$value === void 0 ? '' : _ref$value,
      _ref$autoFocus = _ref.autoFocus,
      autoFocus = _ref$autoFocus === void 0 ? true : _ref$autoFocus,
      onChange = _ref.onChange,
      extraProps = _objectWithoutProperties(_ref, ["value", "autoFocus", "onChange"]);

  /* eslint-disable jsx-a11y/no-autofocus */
  return createElement(TextInput, _extends({
    autoFocus: autoFocus,
    editable: true,
    selectTextOnFocus: true,
    autoCapitalize: "none",
    autoCorrect: false,
    textContentType: "URL",
    value: value,
    onChangeText: onChange,
    placeholder: __('Paste URL')
  }, extraProps));
  /* eslint-enable jsx-a11y/no-autofocus */
}
//# sourceMappingURL=index.native.js.map