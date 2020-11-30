import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import RichText from '../rich-text';
var EditableText = forwardRef(function (props, ref) {
  return createElement(RichText, _extends({
    ref: ref
  }, props, {
    __unstableDisableFormats: true,
    preserveWhiteSpace: true
  }));
});

EditableText.Content = function (_ref) {
  var _ref$value = _ref.value,
      value = _ref$value === void 0 ? '' : _ref$value,
      _ref$tagName = _ref.tagName,
      Tag = _ref$tagName === void 0 ? 'div' : _ref$tagName,
      props = _objectWithoutProperties(_ref, ["value", "tagName"]);

  return createElement(Tag, props, value);
};
/**
 * Renders an editable text input in which text formatting is not allowed.
 */


export default EditableText;
//# sourceMappingURL=index.js.map