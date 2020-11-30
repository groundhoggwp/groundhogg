import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
/**
 * Internal dependencies
 */

import HeadingLevelIcon from './heading-level-icon';
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

export default function HeadingLevelDropdown(_ref) {
  var selectedLevel = _ref.selectedLevel,
      onChange = _ref.onChange;

  var createLevelControl = function createLevelControl(targetLevel, currentLevel, onChangeCallback) {
    var isActive = targetLevel === currentLevel;
    return {
      icon: createElement(HeadingLevelIcon, {
        level: targetLevel,
        isPressed: isActive
      }),
      // translators: %s: heading level e.g: "1", "2", "3"
      title: sprintf(__('Heading %d'), targetLevel),
      isActive: isActive,
      onClick: function onClick() {
        return onChangeCallback(targetLevel);
      }
    };
  };

  return createElement(DropdownMenu, {
    icon: createElement(HeadingLevelIcon, {
      level: selectedLevel
    }),
    controls: HEADING_LEVELS.map(function (index) {
      return createLevelControl(index, selectedLevel, onChange);
    }),
    label: __('Change heading level')
  });
}
//# sourceMappingURL=heading-level-dropdown.native.js.map