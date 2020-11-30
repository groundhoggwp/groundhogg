"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getNameBySite = exports.getIconBySite = void 0;

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _variations = _interopRequireDefault(require("./variations"));

var _icons = require("./icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Retrieves the social service's icon component.
 *
 * @param {string} name key for a social service (lowercase slug)
 *
 * @return {WPComponent} Icon component for social service.
 */
var getIconBySite = function getIconBySite(name) {
  var variation = (0, _lodash.find)(_variations.default, {
    name: name
  });
  return variation ? variation.icon : _icons.ChainIcon;
};
/**
 * Retrieves the display name for the social service.
 *
 * @param {string} name key for a social service (lowercase slug)
 *
 * @return {string} Display name for social service
 */


exports.getIconBySite = getIconBySite;

var getNameBySite = function getNameBySite(name) {
  var variation = (0, _lodash.find)(_variations.default, {
    name: name
  });
  return variation ? variation.title : (0, _i18n.__)('Social Icon');
};

exports.getNameBySite = getNameBySite;
//# sourceMappingURL=social-list.js.map