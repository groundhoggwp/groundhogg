"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Fill;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _element = require("@wordpress/element");

var _useSlot = _interopRequireDefault(require("./use-slot"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useForceUpdate() {
  var _useState = (0, _element.useState)({}),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      setState = _useState2[1];

  return function () {
    return setState({});
  };
}

function Fill(_ref) {
  var name = _ref.name,
      children = _ref.children;
  var slot = (0, _useSlot.default)(name);
  var ref = (0, _element.useRef)({
    rerender: useForceUpdate()
  });
  (0, _element.useEffect)(function () {
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

  return (0, _element.createPortal)(children, slot.ref.current);
}
//# sourceMappingURL=fill.js.map