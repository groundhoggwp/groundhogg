"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = UnitControl;
exports.useCustomUnits = useCustomUnits;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function UnitControl(_ref) {
  var unitsProp = _ref.units,
      props = (0, _objectWithoutProperties2.default)(_ref, ["units"]);
  var units = useCustomUnits(unitsProp);
  return (0, _element.createElement)(_components.__experimentalUnitControl, (0, _extends2.default)({
    units: units
  }, props));
}
/**
 * Filters available units based on values defined by settings.
 *
 * @param {Array} settings Collection of preferred units.
 * @param {Array} units Collection of available units.
 *
 * @return {Array} Filtered units based on settings.
 */


function filterUnitsWithSettings() {
  var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var units = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  return units.filter(function (unit) {
    return settings.includes(unit.value);
  });
}
/**
 * Custom hook to retrieve and consolidate units setting from add_theme_support().
 *
 * @param {Array} units Collection of available units.
 *
 * @return {Array} Filtered units based on settings.
 */


function useCustomUnits(units) {
  var availableUnits = (0, _useEditorFeature.default)('spacing.units');
  var usedUnits = filterUnitsWithSettings(!availableUnits ? [] : availableUnits, units);
  return usedUnits.length === 0 ? false : usedUnits;
}
//# sourceMappingURL=index.js.map