/**
 * External dependencies
 */
export { attr, prop, text, query } from 'hpq';
/**
 * Internal dependencies
 */

export { matcher as node } from './node';
export { matcher as children } from './children';
export function html(selector, multilineTag) {
  return function (domNode) {
    var match = domNode;

    if (selector) {
      match = domNode.querySelector(selector);
    }

    if (!match) {
      return '';
    }

    if (multilineTag) {
      var value = '';
      var length = match.children.length;

      for (var index = 0; index < length; index++) {
        var child = match.children[index];

        if (child.nodeName.toLowerCase() !== multilineTag) {
          continue;
        }

        value += child.outerHTML;
      }

      return value;
    }

    return match.innerHTML;
  };
}
//# sourceMappingURL=matchers.js.map