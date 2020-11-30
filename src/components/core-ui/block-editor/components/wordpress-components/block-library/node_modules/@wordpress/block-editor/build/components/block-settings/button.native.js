"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

/**
 * WordPress dependencies
 */
var _createSlotFill = (0, _components.createSlotFill)('SettingsToolbarButton'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var SettingsButton = function SettingsButton(_ref) {
  var openGeneralSidebar = _ref.openGeneralSidebar;
  return (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Open Settings'),
    icon: _icons.cog,
    onClick: openGeneralSidebar
  });
};

var SettingsButtonFill = function SettingsButtonFill(props) {
  return (0, _element.createElement)(Fill, null, (0, _element.createElement)(SettingsButton, props));
};

var SettingsToolbarButton = (0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch.openGeneralSidebar;

  return {
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    }
  };
})(SettingsButtonFill);
SettingsToolbarButton.Slot = Slot;
var _default = SettingsToolbarButton;
exports.default = _default;
//# sourceMappingURL=button.native.js.map