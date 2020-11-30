import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Fill, ToolbarButton } from '@wordpress/components';
import { displayShortcut } from '@wordpress/keycodes';
export function RichTextToolbarButton(_ref) {
  var name = _ref.name,
      shortcutType = _ref.shortcutType,
      shortcutCharacter = _ref.shortcutCharacter,
      props = _objectWithoutProperties(_ref, ["name", "shortcutType", "shortcutCharacter"]);

  var shortcut;
  var fillName = 'RichText.ToolbarControls';

  if (name) {
    fillName += ".".concat(name);
  }

  if (shortcutType && shortcutCharacter) {
    shortcut = displayShortcut[shortcutType](shortcutCharacter);
  }

  return createElement(Fill, {
    name: fillName
  }, createElement(ToolbarButton, _extends({}, props, {
    shortcut: shortcut
  })));
}
//# sourceMappingURL=toolbar-button.js.map