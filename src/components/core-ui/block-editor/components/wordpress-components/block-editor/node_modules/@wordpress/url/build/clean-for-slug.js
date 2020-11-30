"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.cleanForSlug = cleanForSlug;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Performs some basic cleanup of a string for use as a post slug.
 *
 * This replicates some of what `sanitize_title()` does in WordPress core, but
 * is only designed to approximate what the slug will be.
 *
 * Converts Latin-1 Supplement and Latin Extended-A letters to basic Latin
 * letters. Removes combining diacritical marks. Converts whitespace, periods,
 * and forward slashes to hyphens. Removes any remaining non-word characters
 * except hyphens. Converts remaining string to lowercase. It does not account
 * for octets, HTML entities, or other encoded characters.
 *
 * @param {string} string Title or slug to be processed.
 *
 * @return {string} Processed string.
 */
function cleanForSlug(string) {
  if (!string) {
    return '';
  }

  return (0, _lodash.trim)((0, _lodash.deburr)(string).replace(/[\s\./]+/g, '-').replace(/[^\w-]+/g, '').toLowerCase(), '-');
}
//# sourceMappingURL=clean-for-slug.js.map