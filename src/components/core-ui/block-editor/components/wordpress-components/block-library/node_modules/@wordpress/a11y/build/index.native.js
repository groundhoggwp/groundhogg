"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.speak = speak;

var _filterMessage = _interopRequireDefault(require("./filter-message"));

/**
 * Internal dependencies
 */

/**
 * Update the ARIA live notification area text node.
 *
 * @param {string} message  The message to be announced by Assistive Technologies.
 * @param {string} [ariaLive] The politeness level for aria-live; default: 'polite'.
 */
function speak(message, ariaLive) {
  message = (0, _filterMessage.default)(message); //TODO: Use native module to speak message

  if (ariaLive === 'assertive') {} else {}
}
//# sourceMappingURL=index.native.js.map