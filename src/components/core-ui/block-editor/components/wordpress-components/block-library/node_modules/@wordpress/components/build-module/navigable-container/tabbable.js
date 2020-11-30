import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
import { TAB } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */

import NavigableContainer from './container';
export function TabbableContainer(_ref, ref) {
  var eventToOffset = _ref.eventToOffset,
      props = _objectWithoutProperties(_ref, ["eventToOffset"]);

  var innerEventToOffset = function innerEventToOffset(evt) {
    var keyCode = evt.keyCode,
        shiftKey = evt.shiftKey;

    if (TAB === keyCode) {
      return shiftKey ? -1 : 1;
    } // Allow custom handling of keys besides Tab.
    //
    // By default, TabbableContainer will move focus forward on Tab and
    // backward on Shift+Tab. The handler below will be used for all other
    // events. The semantics for `eventToOffset`'s return
    // values are the following:
    //
    // - +1: move focus forward
    // - -1: move focus backward
    // -  0: don't move focus, but acknowledge event and thus stop it
    // - undefined: do nothing, let the event propagate


    if (eventToOffset) {
      return eventToOffset(evt);
    }
  };

  return createElement(NavigableContainer, _extends({
    ref: ref,
    stopNavigationEvents: true,
    onlyBrowserTabstops: true,
    eventToOffset: innerEventToOffset
  }, props));
}
export default forwardRef(TabbableContainer);
//# sourceMappingURL=tabbable.js.map