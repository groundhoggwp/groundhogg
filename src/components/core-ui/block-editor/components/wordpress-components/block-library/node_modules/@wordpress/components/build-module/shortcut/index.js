import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { isString, isObject } from 'lodash';

function Shortcut(_ref) {
  var shortcut = _ref.shortcut,
      className = _ref.className;

  if (!shortcut) {
    return null;
  }

  var displayText;
  var ariaLabel;

  if (isString(shortcut)) {
    displayText = shortcut;
  }

  if (isObject(shortcut)) {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }

  return createElement("span", {
    className: className,
    "aria-label": ariaLabel
  }, displayText);
}

export default Shortcut;
//# sourceMappingURL=index.js.map