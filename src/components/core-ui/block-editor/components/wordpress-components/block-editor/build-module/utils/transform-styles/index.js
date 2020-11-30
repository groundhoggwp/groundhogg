/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { compose } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import traverse from './traverse';
import urlRewrite from './transforms/url-rewrite';
import wrap from './transforms/wrap';
/**
 * Applies a series of CSS rule transforms to wrap selectors inside a given class and/or rewrite URLs depending on the parameters passed.
 *
 * @param {Array} styles CSS rules.
 * @param {string} wrapperClassName Wrapper Class Name.
 * @return {Array} converted rules.
 */

var transformStyles = function transformStyles(styles) {
  var wrapperClassName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  return map(styles, function (_ref) {
    var css = _ref.css,
        baseURL = _ref.baseURL;
    var transforms = [];

    if (wrapperClassName) {
      transforms.push(wrap(wrapperClassName));
    }

    if (baseURL) {
      transforms.push(urlRewrite(baseURL));
    }

    if (transforms.length) {
      return traverse(css, compose(transforms));
    }

    return css;
  });
};

export default transformStyles;
//# sourceMappingURL=index.js.map