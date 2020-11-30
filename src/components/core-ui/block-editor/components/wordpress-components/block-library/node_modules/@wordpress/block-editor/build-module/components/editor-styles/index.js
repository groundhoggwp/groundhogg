/**
 * External dependencies
 */
import { compact, map } from 'lodash';
/**
 * WordPress dependencies
 */

import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */

import transformStyles from '../../utils/transform-styles';

function EditorStyles(_ref) {
  var styles = _ref.styles;
  useEffect(function () {
    var updatedStyles = transformStyles(styles, '.editor-styles-wrapper');
    var nodes = map(compact(updatedStyles), function (updatedCSS) {
      var node = document.createElement('style');
      node.innerHTML = updatedCSS;
      document.body.appendChild(node);
      return node;
    });
    return function () {
      return nodes.forEach(function (node) {
        return document.body.removeChild(node);
      });
    };
  }, [styles]);
  return null;
}

export default EditorStyles;
//# sourceMappingURL=index.js.map