"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

/**
 * External dependencies
 */
function FormToggle(_ref) {
  var className = _ref.className,
      checked = _ref.checked,
      id = _ref.id,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "checked", "id", "onChange"]);
  var wrapperClasses = (0, _classnames.default)('components-form-toggle', className, {
    'is-checked': checked
  });
  return (0, _element.createElement)("span", {
    className: wrapperClasses
  }, (0, _element.createElement)("input", (0, _extends2.default)({
    className: "components-form-toggle__input",
    id: id,
    type: "checkbox",
    checked: checked,
    onChange: onChange
  }, props)), (0, _element.createElement)("span", {
    className: "components-form-toggle__track"
  }), (0, _element.createElement)("span", {
    className: "components-form-toggle__thumb"
  }));
}

var _default = FormToggle;
exports.default = _default;
//# sourceMappingURL=index.js.map