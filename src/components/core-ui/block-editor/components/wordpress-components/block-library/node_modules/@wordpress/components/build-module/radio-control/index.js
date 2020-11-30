import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useInstanceId } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BaseControl from '../base-control';
export default function RadioControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      selected = _ref.selected,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options;
  var instanceId = useInstanceId(RadioControl);
  var id = "inspector-radio-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return !isEmpty(options) && createElement(BaseControl, {
    label: label,
    id: id,
    help: help,
    className: classnames(className, 'components-radio-control')
  }, options.map(function (option, index) {
    return createElement("div", {
      key: "".concat(id, "-").concat(index),
      className: "components-radio-control__option"
    }, createElement("input", {
      id: "".concat(id, "-").concat(index),
      className: "components-radio-control__input",
      type: "radio",
      name: id,
      value: option.value,
      onChange: onChangeValue,
      checked: option.value === selected,
      "aria-describedby": !!help ? "".concat(id, "__help") : undefined
    }), createElement("label", {
      htmlFor: "".concat(id, "-").concat(index)
    }, option.label));
  }));
}
//# sourceMappingURL=index.js.map