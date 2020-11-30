import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, View } from 'react-native';
export default function BaseControl(_ref) {
  var label = _ref.label,
      help = _ref.help,
      children = _ref.children;
  return createElement(View, {
    accessible: true,
    accessibilityLabel: label
  }, label && createElement(Text, null, label), children, help && createElement(Text, null, help));
}
//# sourceMappingURL=index.native.js.map