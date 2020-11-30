"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = RadioControl;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _baseControl = _interopRequireDefault(require("../base-control"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function RadioControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      selected = _ref.selected,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options;
  var instanceId = (0, _compose.useInstanceId)(RadioControl);
  var id = "inspector-radio-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return !(0, _lodash.isEmpty)(options) && (0, _element.createElement)(_baseControl.default, {
    label: label,
    id: id,
    help: help,
    className: (0, _classnames.default)(className, 'components-radio-control')
  }, options.map(function (option, index) {
    return (0, _element.createElement)("div", {
      key: "".concat(id, "-").concat(index),
      className: "components-radio-control__option"
    }, (0, _element.createElement)("input", {
      id: "".concat(id, "-").concat(index),
      className: "components-radio-control__input",
      type: "radio",
      name: id,
      value: option.value,
      onChange: onChangeValue,
      checked: option.value === selected,
      "aria-describedby": !!help ? "".concat(id, "__help") : undefined
    }), (0, _element.createElement)("label", {
      htmlFor: "".concat(id, "-").concat(index)
    }, option.label));
  }));
}
//# sourceMappingURL=index.js.map