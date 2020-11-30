import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

/**
 * WordPress dependencies
 */
import { useRef, useState, useEffect, createPortal } from '@wordpress/element';
/**
 * Internal dependencies
 */

import useSlot from './use-slot';

function useForceUpdate() {
  var _useState = useState({}),
      _useState2 = _slicedToArray(_useState, 2),
      setState = _useState2[1];

  return function () {
    return setState({});
  };
}

export default function Fill(_ref) {
  var name = _ref.name,
      children = _ref.children;
  var slot = useSlot(name);
  var ref = useRef({
    rerender: useForceUpdate()
  });
  useEffect(function () {
    // We register fills so we can keep track of their existance.
    // Some Slot implementations need to know if there're already fills
    // registered so they can choose to render themselves or not.
    slot.registerFill(ref);
    return function () {
      slot.unregisterFill(ref);
    };
  }, [slot.registerFill, slot.unregisterFill]);

  if (!slot.ref || !slot.ref.current) {
    return null;
  }

  if (typeof children === 'function') {
    children = children(slot.fillProps);
  }

  return createPortal(children, slot.ref.current);
}
//# sourceMappingURL=fill.js.map