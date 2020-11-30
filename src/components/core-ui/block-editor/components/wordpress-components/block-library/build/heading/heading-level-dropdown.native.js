"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = HeadingLevelDropdown;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _headingLevelIcon = _interopRequireDefault(require("./heading-level-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var HEADING_LEVELS = [1, 2, 3, 4, 5, 6];
/** @typedef {import('@wordpress/element').WPComponent} WPComponent */

/**
 * HeadingLevelDropdown props.
 *
 * @typedef WPHeadingLevelDropdownProps
 *
 * @property {number}                 selectedLevel The chosen heading level.
 * @property {(newValue:number)=>any} onChange      Callback to run when
 *                                                  toolbar value is changed.
 */

/**
 * Dropdown for selecting a heading level (1 through 6).
 *
 * @param {WPHeadingLevelDropdownProps} props Component props.
 *
 * @return {WPComponent} The toolbar.
 */

function HeadingLevelDropdown(_ref) {
  var selectedLevel = _ref.selectedLevel,
      onChange = _ref.onChange;

  var createLevelControl = function createLevelControl(targetLevel, currentLevel, onChangeCallback) {
    var isActive = targetLevel === currentLevel;
    return {
      icon: (0, _element.createElement)(_headingLevelIcon.default, {
        level: targetLevel,
        isPressed: isActive
      }),
      // translators: %s: heading level e.g: "1", "2", "3"
      title: (0, _i18n.sprintf)((0, _i18n.__)('Heading %d'), targetLevel),
      isActive: isActive,
      onClick: function onClick() {
        return onChangeCallback(targetLevel);
      }
    };
  };

  return (0, _element.createElement)(_components.DropdownMenu, {
    icon: (0, _element.createElement)(_headingLevelIcon.default, {
      level: selectedLevel
    }),
    controls: HEADING_LEVELS.map(function (index) {
      return createLevelControl(index, selectedLevel, onChange);
    }),
    label: (0, _i18n.__)('Change heading level')
  });
}
//# sourceMappingURL=heading-level-dropdown.native.js.map