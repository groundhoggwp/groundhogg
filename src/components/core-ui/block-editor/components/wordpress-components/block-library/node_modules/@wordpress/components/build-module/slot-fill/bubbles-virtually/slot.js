import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useRef, useLayoutEffect, useContext } from '@wordpress/element';
/**
 * Internal dependencies
 */

import SlotFillContext from './slot-fill-context';
export default function Slot(_ref) {
  var name = _ref.name,
      _ref$fillProps = _ref.fillProps,
      fillProps = _ref$fillProps === void 0 ? {} : _ref$fillProps,
      _ref$as = _ref.as,
      Component = _ref$as === void 0 ? 'div' : _ref$as,
      props = _objectWithoutProperties(_ref, ["name", "fillProps", "as"]);

  var registry = useContext(SlotFillContext);
  var ref = useRef();
  useLayoutEffect(function () {
    registry.registerSlot(name, ref, fillProps);
    return function () {
      registry.unregisterSlot(name, ref);
    }; // We are not including fillProps in the deps because we don't want to
    // unregister and register the slot whenever fillProps change, which would
    // cause the fill to be re-mounted. We are only considering the initial value
    // of fillProps.
  }, [registry.registerSlot, registry.unregisterSlot, name]); // fillProps may be an update that interacts with the layout, so we
  // useLayoutEffect

  useLayoutEffect(function () {
    registry.updateSlot(name, fillProps);
  });
  return createElement(Component, _extends({
    ref: ref
  }, props));
}
//# sourceMappingURL=slot.js.map