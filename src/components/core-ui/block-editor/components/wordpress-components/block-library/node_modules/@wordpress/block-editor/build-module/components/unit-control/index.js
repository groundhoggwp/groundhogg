import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalUnitControl as BaseUnitControl } from '@wordpress/components';
/**
 * Internal dependencies
 */

import useEditorFeature from '../use-editor-feature';
export default function UnitControl(_ref) {
  var unitsProp = _ref.units,
      props = _objectWithoutProperties(_ref, ["units"]);

  var units = useCustomUnits(unitsProp);
  return createElement(BaseUnitControl, _extends({
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


export function useCustomUnits(units) {
  var availableUnits = useEditorFeature('spacing.units');
  var usedUnits = filterUnitsWithSettings(!availableUnits ? [] : availableUnits, units);
  return usedUnits.length === 0 ? false : usedUnits;
}
//# sourceMappingURL=index.js.map