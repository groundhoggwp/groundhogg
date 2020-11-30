"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LevelToolbar;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _levelIcon = _interopRequireDefault(require("./level-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LevelToolbar(_ref) {
  var level = _ref.level,
      onChange = _ref.onChange;
  return (0, _element.createElement)(_components.ToolbarGroup, {
    isCollapsed: true,
    icon: (0, _element.createElement)(_levelIcon.default, {
      level: level
    }),
    controls: [0, 1, 2, 3, 4, 5, 6].map(function (currentLevel) {
      var isActive = currentLevel === level;
      return {
        icon: (0, _element.createElement)(_levelIcon.default, {
          level: currentLevel,
          isPressed: isActive
        }),
        title: currentLevel === 0 ? (0, _i18n.__)('Paragraph') : // translators: %s: heading level e.g: "1", "2", "3"
        (0, _i18n.sprintf)((0, _i18n.__)('Heading %d'), currentLevel),
        isActive: isActive,
        onClick: function onClick() {
          return onChange(currentLevel);
        }
      };
    }),
    label: (0, _i18n.__)('Change heading level')
  });
}
//# sourceMappingURL=level-toolbar.js.map