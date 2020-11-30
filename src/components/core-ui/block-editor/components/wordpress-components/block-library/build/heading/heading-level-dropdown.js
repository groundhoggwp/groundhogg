"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = HeadingLevelDropdown;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _keycodes = require("@wordpress/keycodes");

var _headingLevelIcon = _interopRequireDefault(require("./heading-level-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
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

function HeadingLevelDropdown(_ref) {
  var selectedLevel = _ref.selectedLevel,
      onChange = _ref.onChange;
  return (0, _element.createElement)(_components.Dropdown, {
    popoverProps: POPOVER_PROPS,
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle,
          isOpen = _ref2.isOpen;

      var openOnArrowDown = function openOnArrowDown(event) {
        if (!isOpen && event.keyCode === _keycodes.DOWN) {
          event.preventDefault();
          event.stopPropagation();
          onToggle();
        }
      };

      return (0, _element.createElement)(_components.ToolbarButton, {
        "aria-expanded": isOpen,
        "aria-haspopup": "true",
        icon: (0, _element.createElement)(_headingLevelIcon.default, {
          level: selectedLevel
        }),
        label: (0, _i18n.__)('Change heading level'),
        onClick: onToggle,
        onKeyDown: openOnArrowDown,
        showTooltip: true
      });
    },
    renderContent: function renderContent() {
      return (0, _element.createElement)(_components.Toolbar, {
        className: "block-library-heading-level-toolbar",
        label: (0, _i18n.__)('Change heading level')
      }, (0, _element.createElement)(_components.ToolbarGroup, {
        isCollapsed: false,
        controls: HEADING_LEVELS.map(function (targetLevel) {
          var isActive = targetLevel === selectedLevel;
          return {
            icon: (0, _element.createElement)(_headingLevelIcon.default, {
              level: targetLevel,
              isPressed: isActive
            }),
            title: (0, _i18n.sprintf)( // translators: %s: heading level e.g: "1", "2", "3"
            (0, _i18n.__)('Heading %d'), targetLevel),
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