import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { TextInput } from 'react-native';
/**
 * Internal dependencies
 */

import BaseControl from '../base-control';

function TextareaControl(_ref) {
  var label = _ref.label,
      value = _ref.value,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$rows = _ref.rows,
      rows = _ref$rows === void 0 ? 4 : _ref$rows;
  return createElement(BaseControl, {
    label: label,
    help: help
  }, createElement(TextInput, {
    style: {
      height: 80,
      borderColor: 'gray',
      borderWidth: 1
    },
    value: value,
    onChangeText: onChange,
    numberOfLines: rows,
    multiline: rows > 1,
    textAlignVertical: "top"
  }));
}

export default TextareaControl;
//# sourceMappingURL=index.native.js.map