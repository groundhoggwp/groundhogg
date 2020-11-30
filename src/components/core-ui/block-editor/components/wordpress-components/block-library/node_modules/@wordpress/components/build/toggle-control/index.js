"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ToggleControl;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _formToggle = _interopRequireDefault(require("../form-toggle"));

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
function ToggleControl(_ref) {
  var label = _ref.label,
      checked = _ref.checked,
      help = _ref.help,
      className = _ref.className,
      onChange = _ref.onChange;

  function onChangeToggle(event) {
    onChange(event.target.checked);
  }

  var instanceId = (0, _compose.useInstanceId)(ToggleControl);
  var id = "inspector-toggle-control-".concat(instanceId);
  var describedBy, helpLabel;

  if (help) {
    describedBy = id + '__help';
    helpLabel = (0, _lodash.isFunction)(help) ? help(checked) : help;
  }

  return (0, _element.createElement)(_baseControl.default, {
    id: id,
    help: helpLabel,
    className: (0, _classnames.default)('components-toggle-control', className)
  }, (0, _element.createElement)(_formToggle.default, {
    id: id,
    checked: checked,
    onChange: onChangeToggle,
    "aria-describedby": describedBy
  }), (0, _element.createElement)("label", {
    htmlFor: id,
    className: "components-toggle-control__label"
  }, label));
}
//# sourceMappingURL=index.js.map