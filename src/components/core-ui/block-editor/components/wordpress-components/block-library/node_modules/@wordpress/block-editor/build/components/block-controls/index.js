"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _useDisplayBlockControls = _interopRequireDefault(require("../use-display-block-controls"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _createSlotFill = (0, _components.createSlotFill)('BlockControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function BlockControlsSlot(_ref) {
  var _ref$__experimentalIs = _ref.__experimentalIsExpanded,
      __experimentalIsExpanded = _ref$__experimentalIs === void 0 ? false : _ref$__experimentalIs,
      props = (0, _objectWithoutProperties2.default)(_ref, ["__experimentalIsExpanded"]);

  var accessibleToolbarState = (0, _element.useContext)(_components.__experimentalToolbarContext);
  return (0, _element.createElement)(Slot, (0, _extends2.default)({
    name: buildSlotName(__experimentalIsExpanded)
  }, props, {
    fillProps: accessibleToolbarState
  }));
}

function BlockControlsFill(_ref2) {
  var controls = _ref2.controls,
      __experimentalIsExpanded = _ref2.__experimentalIsExpanded,
      children = _ref2.children;

  if (!(0, _useDisplayBlockControls.default)()) {
    return null;
  }

  return (0, _element.createElement)(Fill, {
    name: buildSlotName(__experimentalIsExpanded)
  }, function (fillProps) {
    // Children passed to BlockControlsFill will not have access to any
    // React Context whose Provider is part of the BlockControlsSlot tree.
    // So we re-create the Provider in this subtree.
    var value = !(0, _lodash.isEmpty)(fillProps) ? fillProps : null;
    return (0, _element.createElement)(_components.__experimentalToolbarContext.Provider, {
      value: value
    }, (0, _element.createElement)(_components.ToolbarGroup, {
      controls: controls
    }), children);
  });
}

var buildSlotName = function buildSlotName(isExpanded) {
  return "BlockControls".concat(isExpanded ? '-expanded' : '');
};

var BlockControls = BlockControlsFill;
BlockControls.Slot = BlockControlsSlot;
var _default = BlockControls;
exports.default = _default;
//# sourceMappingURL=index.js.map