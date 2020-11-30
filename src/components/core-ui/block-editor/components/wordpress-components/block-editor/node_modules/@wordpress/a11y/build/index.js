"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.setup = setup;
exports.speak = speak;

var _domReady = _interopRequireDefault(require("@wordpress/dom-ready"));

var _addIntroText = _interopRequireDefault(require("./add-intro-text"));

var _addContainer = _interopRequireDefault(require("./add-container"));

var _clear = _interopRequireDefault(require("./clear"));

var _filterMessage = _interopRequireDefault(require("./filter-message"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Create the live regions.
 */
function setup() {
  var introText = document.getElementById('a11y-speak-intro-text');
  var containerAssertive = document.getElementById('a11y-speak-assertive');
  var containerPolite = document.getElementById('a11y-speak-polite');

  if (introText === null) {
    (0, _addIntroText.default)();
  }

  if (containerAssertive === null) {
    (0, _addContainer.default)('assertive');
  }

  if (containerPolite === null) {
    (0, _addContainer.default)('polite');
  }
}
/**
 * Run setup on domReady.
 */


(0, _domReady.default)(setup);
/**
 * Allows you to easily announce dynamic interface updates to screen readers using ARIA live regions.
 * This module is inspired by the `speak` function in `wp-a11y.js`.
 *
 * @param {string} message  The message to be announced by assistive technologies.
 * @param {string} [ariaLive] The politeness level for aria-live; default: 'polite'.
 *
 * @example
 * ```js
 * import { speak } from '@wordpress/a11y';
 *
 * // For polite messages that shouldn't interrupt what screen readers are currently announcing.
 * speak( 'The message you want to send to the ARIA live region' );
 *
 * // For assertive messages that should interrupt what screen readers are currently announcing.
 * speak( 'The message you want to send to the ARIA live region', 'assertive' );
 * ```
 */

function speak(message, ariaLive) {
  /*
   * Clear previous messages to allow repeated strings being read out and hide
   * the explanatory text from assistive technologies.
   */
  (0, _clear.default)();
  message = (0, _filterMessage.default)(message);
  var introText = document.getElementById('a11y-speak-intro-text');
  var containerAssertive = document.getElementById('a11y-speak-assertive');
  var containerPolite = document.getElementById('a11y-speak-polite');

  if (containerAssertive && ariaLive === 'assertive') {
    containerAssertive.textContent = message;
  } else if (containerPolite) {
    containerPolite.textContent = message;
  }
  /*
   * Make the explanatory text available to assistive technologies by removing
   * the 'hidden' HTML attribute.
   */


  if (introText) {
    introText.removeAttribute('hidden');
  }
}
//# sourceMappingURL=index.js.map