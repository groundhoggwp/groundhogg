"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useInlineWarning = useInlineWarning;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
function useInlineWarning(_ref) {
  var ref = _ref.ref;
  (0, _element.useEffect)(function () {
    if (process.env.NODE_ENV === 'development') {
      var target = ref.current;
      var defaultView = target.ownerDocument.defaultView;
      var computedStyle = defaultView.getComputedStyle(target);

      if (computedStyle.display === 'inline') {
        // eslint-disable-next-line no-console
        console.warn('RichText cannot be used with an inline container. Please use a different tagName.');
      }
    }
  }, []);
}
//# sourceMappingURL=use-inline-warning.js.map