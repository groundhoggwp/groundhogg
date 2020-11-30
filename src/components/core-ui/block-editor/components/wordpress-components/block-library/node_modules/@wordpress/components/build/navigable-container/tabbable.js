"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.TabbableContainer = TabbableContainer;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _keycodes = require("@wordpress/keycodes");

var _container = _interopRequireDefault(require("./container"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function TabbableContainer(_ref, ref) {
  var eventToOffset = _ref.eventToOffset,
      props = (0, _objectWithoutProperties2.default)(_ref, ["eventToOffset"]);

  var innerEventToOffset = function innerEventToOffset(evt) {
    var keyCode = evt.keyCode,
        shiftKey = evt.shiftKey;

    if (_keycodes.TAB === keyCode) {
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

  return (0, _element.createElement)(_container.default, (0, _extends2.default)({
    ref: ref,
    stopNavigationEvents: true,
    onlyBrowserTabstops: true,
    eventToOffset: innerEventToOffset
  }, props));
}

var _default = (0, _element.forwardRef)(TabbableContainer);

exports.default = _default;
//# sourceMappingURL=tabbable.js.map