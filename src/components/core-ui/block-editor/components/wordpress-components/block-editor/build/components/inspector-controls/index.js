"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _useDisplayBlockControls = _interopRequireDefault(require("../use-display-block-controls"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _createSlotFill = (0, _components.createSlotFill)('InspectorControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function InspectorControls(_ref) {
  var children = _ref.children;
  return (0, _useDisplayBlockControls.default)() ? (0, _element.createElement)(Fill, null, children) : null;
}

InspectorControls.Slot = Slot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inspector-controls/README.md
 */

var _default = InspectorControls;
exports.default = _default;
//# sourceMappingURL=index.js.map