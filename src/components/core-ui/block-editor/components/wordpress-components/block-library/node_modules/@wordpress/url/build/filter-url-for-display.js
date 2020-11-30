"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.filterURLForDisplay = filterURLForDisplay;

/**
 * Returns a URL for display.
 *
 * @param {string} url Original URL.
 *
 * @example
 * ```js
 * const displayUrl = filterURLForDisplay( 'https://www.wordpress.org/gutenberg/' ); // wordpress.org/gutenberg
 * ```
 *
 * @return {string} Displayed URL.
 */
function filterURLForDisplay(url) {
  // Remove protocol and www prefixes.
  var filteredURL = url.replace(/^(?:https?:)\/\/(?:www\.)?/, ''); // Ends with / and only has that single slash, strip it.

  if (filteredURL.match(/^[^\/]+\/$/)) {
    return filteredURL.replace('/', '');
  }

  return filteredURL;
}
//# sourceMappingURL=filter-url-for-display.js.map