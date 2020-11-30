"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _warning = _interopRequireDefault(require("../warning"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var warning = (0, _element.createElement)(_warning.default, {
  className: "block-editor-block-list__block-crash-warning"
}, (0, _i18n.__)('This block has encountered an error and cannot be previewed.'));

var _default = function _default() {
  return warning;
};

exports.default = _default;
//# sourceMappingURL=block-crash-warning.js.map