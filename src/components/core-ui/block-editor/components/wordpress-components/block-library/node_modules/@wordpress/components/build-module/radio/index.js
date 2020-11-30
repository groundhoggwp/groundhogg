import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Radio as ReakitRadio } from 'reakit/Radio';
/**
 * WordPress dependencies
 */

import { useContext, forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import Button from '../button';
import RadioContext from '../radio-context';

function Radio(_ref, ref) {
  var children = _ref.children,
      value = _ref.value,
      props = _objectWithoutProperties(_ref, ["children", "value"]);

  var radioContext = useContext(RadioContext);
  var checked = radioContext.state === value;
  return createElement(ReakitRadio, _extends({
    ref: ref,
    as: Button,
    isPrimary: checked,
    isSecondary: !checked,
    value: value
  }, radioContext, props), children || value);
}

export default forwardRef(Radio);
//# sourceMappingURL=index.js.map