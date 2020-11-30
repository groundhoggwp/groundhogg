"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

/**
 * External dependencies
 */
function Shortcut(_ref) {
  var shortcut = _ref.shortcut,
      className = _ref.className;

  if (!shortcut) {
    return null;
  }

  var displayText;
  var ariaLabel;

  if ((0, _lodash.isString)(shortcut)) {
    displayText = shortcut;
  }

  if ((0, _lodash.isObject)(shortcut)) {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }

  return (0, _element.createElement)("span", {
    className: className,
    "aria-label": ariaLabel
  }, displayText);
}

var _default = Shortcut;
exports.default = _default;
//# sourceMappingURL=index.js.map