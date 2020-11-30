"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _a11y = require("@wordpress/a11y");

/**
 * WordPress dependencies
 */
var _default = {
  SPEAK: function SPEAK(action) {
    (0, _a11y.speak)(action.message, action.ariaLive || 'assertive');
  }
};
exports.default = _default;
//# sourceMappingURL=controls.js.map