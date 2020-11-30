"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _context = require("../block-edit/context");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _createSlotFill = (0, _components.createSlotFill)('BlockFormatControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function BlockFormatControlsSlot(props) {
  var accessibleToolbarState = (0, _element.useContext)(_components.__experimentalToolbarContext);
  return (0, _element.createElement)(Slot, (0, _extends2.default)({}, props, {
    fillProps: accessibleToolbarState
  }));
}

function BlockFormatControlsFill(props) {
  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      isSelected = _useBlockEditContext.isSelected;

  if (!isSelected) {
    return null;
  }

  return (0, _element.createElement)(Fill, null, function (fillProps) {
    var value = !(0, _lodash.isEmpty)(fillProps) ? fillProps : null;
    return (0, _element.createElement)(_components.__experimentalToolbarContext.Provider, {
      value: value
    }, props.children);
  });
}

var BlockFormatControls = BlockFormatControlsFill;
BlockFormatControls.Slot = BlockFormatControlsSlot;
var _default = BlockFormatControls;
exports.default = _default;
//# sourceMappingURL=index.js.map