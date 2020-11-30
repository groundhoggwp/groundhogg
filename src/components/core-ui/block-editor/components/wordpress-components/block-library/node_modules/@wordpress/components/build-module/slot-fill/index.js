import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement, Fragment } from "@wordpress/element";

/**
 * Internal dependencies
 */
import BaseSlot from './slot';
import BaseFill from './fill';
import Provider from './context';
import BubblesVirtuallySlot from './bubbles-virtually/slot';
import BubblesVirtuallyFill from './bubbles-virtually/fill';
import useSlot from './bubbles-virtually/use-slot';
export function Slot(_ref) {
  var bubblesVirtually = _ref.bubblesVirtually,
      props = _objectWithoutProperties(_ref, ["bubblesVirtually"]);

  if (bubblesVirtually) {
    return createElement(BubblesVirtuallySlot, props);
  }

  return createElement(BaseSlot, props);
}
export function Fill(props) {
  // We're adding both Fills here so they can register themselves before
  // their respective slot has been registered. Only the Fill that has a slot
  // will render. The other one will return null.
  return createElement(Fragment, null, createElement(BaseFill, props), createElement(BubblesVirtuallyFill, props));
}
export function createSlotFill(name) {
  var FillComponent = function FillComponent(props) {
    return createElement(Fill, _extends({
      name: name
    }, props));
  };

  FillComponent.displayName = name + 'Fill';

  var SlotComponent = function SlotComponent(props) {
    return createElement(Slot, _extends({
      name: name
    }, props));
  };

  SlotComponent.displayName = name + 'Slot';
  return {
    Fill: FillComponent,
    Slot: SlotComponent
  };
}
export { useSlot, Provider };
//# sourceMappingURL=index.js.map