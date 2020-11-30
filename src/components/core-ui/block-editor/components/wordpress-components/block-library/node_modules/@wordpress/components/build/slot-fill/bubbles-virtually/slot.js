"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Slot;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _slotFillContext = _interopRequireDefault(require("./slot-fill-context"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Slot(_ref) {
  var name = _ref.name,
      _ref$fillProps = _ref.fillProps,
      fillProps = _ref$fillProps === void 0 ? {} : _ref$fillProps,
      _ref$as = _ref.as,
      Component = _ref$as === void 0 ? 'div' : _ref$as,
      props = (0, _objectWithoutProperties2.default)(_ref, ["name", "fillProps", "as"]);
  var registry = (0, _element.useContext)(_slotFillContext.default);
  var ref = (0, _element.useRef)();
  (0, _element.useLayoutEffect)(function () {
    registry.registerSlot(name, ref, fillProps);
    return function () {
      registry.unregisterSlot(name, ref);
    }; // We are not including fillProps in the deps because we don't want to
    // unregister and register the slot whenever fillProps change, which would
    // cause the fill to be re-mounted. We are only considering the initial value
    // of fillProps.
  }, [registry.registerSlot, registry.unregisterSlot, name]); // fillProps may be an update that interacts with the layout, so we
  // useLayoutEffect

  (0, _element.useLayoutEffect)(function () {
    registry.updateSlot(name, fillProps);
  });
  return (0, _element.createElement)(Component, (0, _extends2.default)({
    ref: ref
  }, props));
}
//# sourceMappingURL=slot.js.map