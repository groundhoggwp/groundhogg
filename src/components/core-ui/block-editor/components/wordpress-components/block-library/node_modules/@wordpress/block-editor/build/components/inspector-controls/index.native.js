"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _react = _interopRequireDefault(require("react"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _context = require("../block-edit/context");

var _blockSettings = require("../block-settings");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _createSlotFill = (0, _components.createSlotFill)('InspectorControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var FillWithSettingsButton = function FillWithSettingsButton(_ref) {
  var children = _ref.children,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children"]);

  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      isSelected = _useBlockEditContext.isSelected;

  if (!isSelected) {
    return null;
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(Fill, props, (0, _element.createElement)(_components.BottomSheetConsumer, null, function () {
    return (0, _element.createElement)(_reactNative.View, null, children);
  })), _react.default.Children.count(children) > 0 && (0, _element.createElement)(_blockSettings.BlockSettingsButton, null));
};

var InspectorControls = FillWithSettingsButton;
InspectorControls.Slot = Slot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inspector-controls/README.md
 */

var _default = InspectorControls;
exports.default = _default;
//# sourceMappingURL=index.native.js.map