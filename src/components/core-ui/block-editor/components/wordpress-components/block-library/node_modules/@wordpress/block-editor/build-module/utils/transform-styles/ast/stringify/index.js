// Adapted from https://github.com/reworkcss/css
// because we needed to remove source map support.

/**
 * Internal dependencies
 */
import Compressed from './compress';
import Identity from './identity';
/**
 * Stringfy the given AST `node`.
 *
 * Options:
 *
 *  - `compress` space-optimized output
 *  - `sourcemap` return an object with `.code` and `.map`
 *
 * @param {Object} node
 * @param {Object} [options]
 * @return {string}
 */

export default function (node, options) {
  options = options || {};
  var compiler = options.compress ? new Compressed(options) : new Identity(options);
  var code = compiler.compile(node);
  return code;
}
//# sourceMappingURL=index.js.map