"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LineHeightControl;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _keycodes = require("@wordpress/keycodes");

var _utils = require("./utils");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LineHeightControl(_ref) {
  var lineHeight = _ref.value,
      onChange = _ref.onChange;
  var isDefined = (0, _utils.isLineHeightDefined)(lineHeight);

  var handleOnKeyDown = function handleOnKeyDown(event) {
    var keyCode = event.keyCode;

    if (keyCode === _keycodes.ZERO && !isDefined) {
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
      case "".concat(_utils.STEP):
        // Increment by step value
        adjustedNextValue = _utils.BASE_DEFAULT_VALUE + _utils.STEP;
        break;

      case '0':
        // Decrement by step value
        adjustedNextValue = _utils.BASE_DEFAULT_VALUE - _utils.STEP;
        break;
    }

    onChange(adjustedNextValue);
  };

  var value = isDefined ? lineHeight : _utils.RESET_VALUE;
  return (0, _element.createElement)("div", {
    className: "block-editor-line-height-control"
  }, (0, _element.createElement)(_components.TextControl, {
    autoComplete: "off",
    onKeyDown: handleOnKeyDown,
    onChange: handleOnChange,
    label: (0, _i18n.__)('Line height'),
    placeholder: _utils.BASE_DEFAULT_VALUE,
    step: _utils.STEP,
    type: "number",
    value: value,
    min: 0
  }));
}
//# sourceMappingURL=index.js.map