"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _visuallyHidden = _interopRequireDefault(require("../visually-hidden"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function BaseControl(_ref) {
  var id = _ref.id,
      label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      help = _ref.help,
      className = _ref.className,
      children = _ref.children;
  return (0, _element.createElement)("div", {
    className: (0, _classnames.default)('components-base-control', className)
  }, (0, _element.createElement)("div", {
    className: "components-base-control__field"
  }, label && id && (hideLabelFromVision ? (0, _element.createElement)(_visuallyHidden.default, {
    as: "label",
    htmlFor: id
  }, label) : (0, _element.createElement)("label", {
    className: "components-base-control__label",
    htmlFor: id
  }, label)), label && !id && (hideLabelFromVision ? (0, _element.createElement)(_visuallyHidden.default, {
    as: "label"
  }, label) : (0, _element.createElement)(BaseControl.VisualLabel, null, label)), children), !!help && (0, _element.createElement)("p", {
    id: id + '__help',
    className: "components-base-control__help"
  }, help));
}

BaseControl.VisualLabel = function (_ref2) {
  var className = _ref2.className,
      children = _ref2.children;
  className = (0, _classnames.default)('components-base-control__label', className);
  return (0, _element.createElement)("span", {
    className: className
  }, children);
};

var _default = BaseControl;
exports.default = _default;
//# sourceMappingURL=index.js.map