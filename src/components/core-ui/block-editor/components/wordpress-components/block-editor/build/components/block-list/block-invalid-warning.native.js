"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockInvalidWarning;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _warning = _interopRequireDefault(require("../warning"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockInvalidWarning(_ref) {
  var blockTitle = _ref.blockTitle,
      icon = _ref.icon;
  var accessibilityLabel = (0, _i18n.sprintf)(
  /* translators: accessibility text for blocks with invalid content. %d: localized block title */
  (0, _i18n.__)('%s block. This block has invalid content'), blockTitle);
  return (0, _element.createElement)(_warning.default, {
    title: blockTitle,
    message: (0, _i18n.__)('Problem displaying block'),
    icon: icon,
    accessible: true,
    accessibilityLabel: accessibilityLabel
  });
}
//# sourceMappingURL=block-invalid-warning.native.js.map