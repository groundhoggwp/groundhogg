import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
export default function useClientWidth(ref, dependencies) {
  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      clientWidth = _useState2[0],
      setClientWidth = _useState2[1];

  function calculateClientWidth() {
    setClientWidth(ref.current.clientWidth);
  }

  useEffect(calculateClientWidth, dependencies);
  useEffect(function () {
    var defaultView = ref.current.ownerDocument.defaultView;
    defaultView.addEventListener('resize', calculateClientWidth);
    return function () {
      defaultView.removeEventListener('resize', calculateClientWidth);
    };
  }, []);
  return clientWidth;
}
//# sourceMappingURL=use-client-width.js.map