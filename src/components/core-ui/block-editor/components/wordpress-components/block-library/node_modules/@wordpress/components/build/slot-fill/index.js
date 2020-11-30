"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Slot = Slot;
exports.Fill = Fill;
exports.createSlotFill = createSlotFill;
Object.defineProperty(exports, "Provider", {
  enumerable: true,
  get: function get() {
    return _context.default;
  }
});
Object.defineProperty(exports, "useSlot", {
  enumerable: true,
  get: function get() {
    return _useSlot.default;
  }
});

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _slot = _interopRequireDefault(require("./slot"));

var _fill = _interopRequireDefault(require("./fill"));

var _context = _interopRequireDefault(require("./context"));

var _slot2 = _interopRequireDefault(require("./bubbles-virtually/slot"));

var _fill2 = _interopRequireDefault(require("./bubbles-virtually/fill"));

var _useSlot = _interopRequireDefault(require("./bubbles-virtually/use-slot"));

/**
 * Internal dependencies
 */
function Slot(_ref) {
  var bubblesVirtually = _ref.bubblesVirtually,
      props = (0, _objectWithoutProperties2.default)(_ref, ["bubblesVirtually"]);

  if (bubblesVirtually) {
    return (0, _element.createElement)(_slot2.default, props);
  }

  return (0, _element.createElement)(_slot.default, props);
}

function Fill(props) {
  // We're adding both Fills here so they can register themselves before
  // their respective slot has been registered. Only the Fill that has a slot
  // will render. The other one will return null.
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_fill.default, props), (0, _element.createElement)(_fill2.default, props));
}

function createSlotFill(name) {
  var FillComponent = function FillComponent(props) {
    return (0, _element.createElement)(Fill, (0, _extends2.default)({
      name: name
    }, props));
  };

  FillComponent.displayName = name + 'Fill';

  var SlotComponent = function SlotComponent(props) {
    return (0, _element.createElement)(Slot, (0, _extends2.default)({
      name: name
    }, props));
  };

  SlotComponent.displayName = name + 'Slot';
  return {
    Fill: FillComponent,
    Slot: SlotComponent
  };
}
//# sourceMappingURL=index.js.map