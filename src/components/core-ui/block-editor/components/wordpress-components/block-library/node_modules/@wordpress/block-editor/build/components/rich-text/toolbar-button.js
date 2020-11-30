"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.RichTextToolbarButton = RichTextToolbarButton;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _keycodes = require("@wordpress/keycodes");

/**
 * WordPress dependencies
 */
function RichTextToolbarButton(_ref) {
  var name = _ref.name,
      shortcutType = _ref.shortcutType,
      shortcutCharacter = _ref.shortcutCharacter,
      props = (0, _objectWithoutProperties2.default)(_ref, ["name", "shortcutType", "shortcutCharacter"]);
  var shortcut;
  var fillName = 'RichText.ToolbarControls';

  if (name) {
    fillName += ".".concat(name);
  }

  if (shortcutType && shortcutCharacter) {
    shortcut = _keycodes.displayShortcut[shortcutType](shortcutCharacter);
  }

  return (0, _element.createElement)(_components.Fill, {
    name: fillName
  }, (0, _element.createElement)(_components.ToolbarButton, (0, _extends2.default)({}, props, {
    shortcut: shortcut
  })));
}
//# sourceMappingURL=toolbar-button.js.map