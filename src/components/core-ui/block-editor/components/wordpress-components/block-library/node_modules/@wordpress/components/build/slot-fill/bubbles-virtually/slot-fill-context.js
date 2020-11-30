"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _warning = _interopRequireDefault(require("@wordpress/warning"));

/**
 * WordPress dependencies
 */
var SlotFillContext = (0, _element.createContext)({
  slots: {},
  fills: {},
  registerSlot: function registerSlot() {
    typeof process !== "undefined" && process.env && process.env.NODE_ENV !== "production" ? (0, _warning.default)('Components must be wrapped within `SlotFillProvider`. ' + 'See https://developer.wordpress.org/block-editor/components/slot-fill/') : void 0;
  },
  updateSlot: function updateSlot() {},
  unregisterSlot: function unregisterSlot() {},
  registerFill: function registerFill() {},
  unregisterFill: function unregisterFill() {}
});
var _default = SlotFillContext;
exports.default = _default;
//# sourceMappingURL=slot-fill-context.js.map