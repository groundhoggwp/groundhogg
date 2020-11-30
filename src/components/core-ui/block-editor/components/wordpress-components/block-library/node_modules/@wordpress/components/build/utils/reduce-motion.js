"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.reduceMotion = reduceMotion;

/**
 * Allows users to opt-out of animations via OS-level preferences.
 *
 * @param {string} prop CSS Property name
 * @return {string} Generated CSS code for the reduced style
 */
function reduceMotion() {
  var prop = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'transition';
  var style;

  switch (prop) {
    case 'transition':
      style = 'transition-duration: 0ms;';
      break;

    case 'animation':
      style = 'animation-duration: 1ms;';
      break;

    default:
      style = "\n\t\t\t\tanimation-duration: 1ms;\n\t\t\t\ttransition-duration: 0ms;\n\t\t\t";
  }

  return "\n\t\t@media ( prefers-reduced-motion: reduce ) {\n\t\t\t".concat(style, ";\n\t\t}\n\t");
}
//# sourceMappingURL=reduce-motion.js.map