import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import TextareaAutosize from 'react-autosize-textarea';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import EditableText from '../editable-text';
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/plain-text/README.md
 */

var PlainText = forwardRef(function (_ref, ref) {
  var __experimentalVersion = _ref.__experimentalVersion,
      props = _objectWithoutProperties(_ref, ["__experimentalVersion"]);

  if (__experimentalVersion === 2) {
    return createElement(EditableText, _extends({
      ref: ref
    }, props));
  }

  var className = props.className,
      _onChange = props.onChange,
      remainingProps = _objectWithoutProperties(props, ["className", "onChange"]);

  return createElement(TextareaAutosize, _extends({
    ref: ref,
    className: classnames('block-editor-plain-text', className),
    onChange: function onChange(event) {
      return _onChange(event.target.value);
    }
  }, remainingProps));
});
export default PlainText;
//# sourceMappingURL=index.js.map