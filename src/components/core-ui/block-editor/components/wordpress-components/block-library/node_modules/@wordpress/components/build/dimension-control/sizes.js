"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.findSizeBySlug = void 0;

var _i18n = require("@wordpress/i18n");

/**
 * Sizes
 *
 * defines the sizes used in dimension controls
 * all hardcoded `size` values are based on the value of
 * the Sass variable `$block-padding` from
 * `packages/block-editor/src/components/dimension-control/sizes.js`.
 */

/**
 * WordPress dependencies
 */

/**
 * Finds the correct size object from the provided sizes
 * table by size slug (eg: `medium`)
 *
 * @param  {Array}  sizes containing objects for each size definition
 * @param  {string} slug  a string representation of the size (eg: `medium`)
 * @return {Object}       the matching size definition
 */
var findSizeBySlug = function findSizeBySlug(sizes, slug) {
  return sizes.find(function (size) {
    return slug === size.slug;
  });
};

exports.findSizeBySlug = findSizeBySlug;
var _default = [{
  name: (0, _i18n.__)('None'),
  slug: 'none'
}, {
  name: (0, _i18n.__)('Small'),
  slug: 'small'
}, {
  name: (0, _i18n.__)('Medium'),
  slug: 'medium'
}, {
  name: (0, _i18n.__)('Large'),
  slug: 'large'
}, {
  name: (0, _i18n.__)('Extra Large'),
  slug: 'xlarge'
}];
exports.default = _default;
//# sourceMappingURL=sizes.js.map