"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */

/*
 * A `React.useEffect` that will not run on the first render.
 * Source:
 * https://github.com/reakit/reakit/blob/master/packages/reakit-utils/src/useUpdateEffect.ts
 */
function useUpdateEffect(effect, deps) {
  var mounted = (0, _element.useRef)(false);
  (0, _element.useEffect)(function () {
    if (mounted.current) {
      return effect();
    }

    mounted.current = true;
    return undefined;
  }, deps);
}

var _default = useUpdateEffect;
exports.default = _default;
//# sourceMappingURL=use-update-effect.js.map