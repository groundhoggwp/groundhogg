import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { ZERO } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */

import { BASE_DEFAULT_VALUE, RESET_VALUE, STEP, isLineHeightDefined } from './utils';
export default function LineHeightControl(_ref) {
  var lineHeight = _ref.value,
      onChange = _ref.onChange;
  var isDefined = isLineHeightDefined(lineHeight);

  var handleOnKeyDown = function handleOnKeyDown(event) {
    var keyCode = event.keyCode;

    if (keyCode === ZERO && !isDefined) {
      /**
       * Prevents the onChange callback from firing, which prevents
       * the logic from assuming the change was triggered from
       * an input arrow CLICK.
       */
      event.preventDefault();
      onChange('0');
    }
  };

  var handleOnChange = function handleOnChange(nextValue) {
    // Set the next value without modification if lineHeight has been defined
    if (isDefined) {
      onChange(nextValue);
      return;
    } // Otherwise...

    /**
     * The following logic handles the initial up/down arrow CLICK of the
     * input element. This is so that the next values (from an undefined value state)
     * are more better suited for line-height rendering.
     */


    var adjustedNextValue = nextValue;

    switch (nextValue) {
      case "".concat(STEP):
        // Increment by step value
        adjustedNextValue = BASE_DEFAULT_VALUE + STEP;
        break;

      case '0':
        // Decrement by step value
        adjustedNextValue = BASE_DEFAULT_VALUE - STEP;
        break;
    }

    onChange(adjustedNextValue);
  };

  var value = isDefined ? lineHeight : RESET_VALUE;
  return createElement("div", {
    className: "block-editor-line-height-control"
  }, createElement(TextControl, {
    autoComplete: "off",
    onKeyDown: handleOnKeyDown,
    onChange: handleOnChange,
    label: __('Line height'),
    placeholder: BASE_DEFAULT_VALUE,
    step: STEP,
    type: "number",
    value: value,
    min: 0
  }));
}
//# sourceMappingURL=index.js.map