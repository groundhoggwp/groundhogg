"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _button = _interopRequireDefault(require("../button"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ModalHeader = function ModalHeader(_ref) {
  var icon = _ref.icon,
      title = _ref.title,
      onClose = _ref.onClose,
      closeLabel = _ref.closeLabel,
      headingId = _ref.headingId,
      isDismissible = _ref.isDismissible;
  var label = closeLabel ? closeLabel : (0, _i18n.__)('Close dialog');
  return (0, _element.createElement)("div", {
    className: "components-modal__header"
  }, (0, _element.createElement)("div", {
    className: "components-modal__header-heading-container"
  }, icon && (0, _element.createElement)("span", {
    className: "components-modal__icon-container",
    "aria-hidden": true
  }, icon), title && (0, _element.createElement)("h1", {
    id: headingId,
    className: "components-modal__header-heading"
  }, title)), isDismissible && (0, _element.createElement)(_button.default, {
    onClick: onClose,
    icon: _icons.close,
    label: label
  }));
};

var _default = ModalHeader;
exports.default = _default;
//# sourceMappingURL=header.js.map