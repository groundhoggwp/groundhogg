"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getProtocol = getProtocol;

/**
 * Returns the protocol part of the URL.
 *
 * @param {string} url The full URL.
 *
 * @example
 * ```js
 * const protocol1 = getProtocol( 'tel:012345678' ); // 'tel:'
 * const protocol2 = getProtocol( 'https://wordpress.org' ); // 'https:'
 * ```
 *
 * @return {string|void} The protocol part of the URL.
 */
function getProtocol(url) {
  var matches = /^([^\s:]+:)/.exec(url);

  if (matches) {
    return matches[1];
  }
}
//# sourceMappingURL=get-protocol.js.map