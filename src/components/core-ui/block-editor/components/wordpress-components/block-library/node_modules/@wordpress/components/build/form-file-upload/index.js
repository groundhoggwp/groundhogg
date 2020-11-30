"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _button = _interopRequireDefault(require("../button"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function FormFileUpload(_ref) {
  var accept = _ref.accept,
      children = _ref.children,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      render = _ref.render,
      props = (0, _objectWithoutProperties2.default)(_ref, ["accept", "children", "multiple", "onChange", "render"]);
  var ref = (0, _element.useRef)();

  var openFileDialog = function openFileDialog() {
    ref.current.click();
  };

  var ui = render ? render({
    openFileDialog: openFileDialog
  }) : (0, _element.createElement)(_button.default, (0, _extends2.default)({
    onClick: openFileDialog
  }, props), children);
  return (0, _element.createElement)("div", {
    className: "components-form-file-upload"
  }, ui, (0, _element.createElement)("input", {
    type: "file",
    ref: ref,
    multiple: multiple,
    style: {
      display: 'none'
    },
    accept: accept,
    onChange: onChange
  }));
}

var _default = FormFileUpload;
exports.default = _default;
//# sourceMappingURL=index.js.map