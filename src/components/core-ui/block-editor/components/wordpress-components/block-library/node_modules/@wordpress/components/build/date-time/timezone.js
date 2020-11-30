"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _date = require("@wordpress/date");

var _tooltip = _interopRequireDefault(require("../tooltip"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Displays timezone information when user timezone is different from site timezone.
 */
var TimeZone = function TimeZone() {
  var _getDateSettings = (0, _date.__experimentalGetSettings)(),
      timezone = _getDateSettings.timezone; // Convert timezone offset to hours.


  var userTimezoneOffset = -1 * (new Date().getTimezoneOffset() / 60); // System timezone and user timezone match, nothing needed.
  // Compare as numbers because it comes over as string.

  if (Number(timezone.offset) === userTimezoneOffset) {
    return null;
  }

  var offsetSymbol = timezone.offset >= 0 ? '+' : '';
  var zoneAbbr = '' !== timezone.abbr && isNaN(timezone.abbr) ? timezone.abbr : "UTC".concat(offsetSymbol).concat(timezone.offset);
  var timezoneDetail = 'UTC' === timezone.string ? (0, _i18n.__)('Coordinated Universal Time') : "(".concat(zoneAbbr, ") ").concat(timezone.string.replace('_', ' '));
  return (0, _element.createElement)(_tooltip.default, {
    position: "top center",
    text: timezoneDetail
  }, (0, _element.createElement)("div", {
    className: "components-datetime__timezone"
  }, zoneAbbr));
};

var _default = TimeZone;
exports.default = _default;
//# sourceMappingURL=timezone.js.map