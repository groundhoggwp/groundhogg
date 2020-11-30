import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Dropdown, Toolbar, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { DOWN } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */

import HeadingLevelIcon from './heading-level-icon';
var HEADING_LEVELS = [1, 2, 3, 4, 5, 6];
var POPOVER_PROPS = {
  className: 'block-library-heading-level-dropdown',
  isAlternate: true
};
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
  return createElement(Dropdown, {
    popoverProps: POPOVER_PROPS,
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle,
          isOpen = _ref2.isOpen;

      var openOnArrowDown = function openOnArrowDown(event) {
        if (!isOpen && event.keyCode === DOWN) {
          event.preventDefault();
          event.stopPropagation();
          onToggle();
        }
      };

      return createElement(ToolbarButton, {
        "aria-expanded": isOpen,
        "aria-haspopup": "true",
        icon: createElement(HeadingLevelIcon, {
          level: selectedLevel
        }),
        label: __('Change heading level'),
        onClick: onToggle,
        onKeyDown: openOnArrowDown,
        showTooltip: true
      });
    },
    renderContent: function renderContent() {
      return createElement(Toolbar, {
        className: "block-library-heading-level-toolbar",
        label: __('Change heading level')
      }, createElement(ToolbarGroup, {
        isCollapsed: false,
        controls: HEADING_LEVELS.map(function (targetLevel) {
          var isActive = targetLevel === selectedLevel;
          return {
            icon: createElement(HeadingLevelIcon, {
              level: targetLevel,
              isPressed: isActive
            }),
            title: sprintf( // translators: %s: heading level e.g: "1", "2", "3"
            __('Heading %d'), targetLevel),
            isActive: isActive,
            onClick: function onClick() {
              onChange(targetLevel);
            }
          };
        })
      }));
    }
  });
}
//# sourceMappingURL=heading-level-dropdown.js.map