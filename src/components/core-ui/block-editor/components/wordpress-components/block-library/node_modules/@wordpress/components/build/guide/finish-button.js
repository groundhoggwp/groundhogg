"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = FinishButton;

var _element = require("@wordpress/element");

var _button = _interopRequireDefault(require("../button"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function FinishButton(_ref) {
  var className = _ref.className,
      onClick = _ref.onClick,
      children = _ref.children;
  var button = (0, _element.useRef)(null); // Focus the button on mount if nothing else is focused. This prevents a
  // focus loss when the 'Next' button is swapped out.

  (0, _element.useLayoutEffect)(function () {
    if (!document.activeElement || document.activeElement === document.body) {
      button.current.focus();
    }
  }, [button]);
  return (0, _element.createElement)(_button.default, {
    ref: button,
    className: className,
    isPrimary: true,
    onClick: onClick
  }, children);
}
//# sourceMappingURL=finish-button.js.map