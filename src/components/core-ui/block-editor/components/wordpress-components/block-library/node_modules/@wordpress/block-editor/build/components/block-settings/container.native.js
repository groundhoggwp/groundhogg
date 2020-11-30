"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.blockSettingsScreens = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _containerNative = _interopRequireDefault(require("./container.native.scss"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var blockSettingsScreens = {
  settings: 'Settings',
  color: 'Color'
};
exports.blockSettingsScreens = blockSettingsScreens;

function BottomSheetSettings(_ref) {
  var editorSidebarOpened = _ref.editorSidebarOpened,
      closeGeneralSidebar = _ref.closeGeneralSidebar,
      settings = _ref.settings,
      props = (0, _objectWithoutProperties2.default)(_ref, ["editorSidebarOpened", "closeGeneralSidebar", "settings"]);
  return (0, _element.createElement)(_components.BottomSheet, (0, _extends2.default)({
    isVisible: editorSidebarOpened,
    onClose: closeGeneralSidebar,
    hideHeader: true,
    contentStyle: _containerNative.default.content
  }, props), (0, _element.createElement)(_components.BottomSheet.NavigationContainer, {
    animate: true,
    main: true
  }, (0, _element.createElement)(_components.BottomSheet.NavigationScreen, {
    name: blockSettingsScreens.settings
  }, (0, _element.createElement)(_blockEditor.InspectorControls.Slot, null)), (0, _element.createElement)(_components.BottomSheet.NavigationScreen, {
    name: blockSettingsScreens.color
  }, (0, _element.createElement)(_components.ColorSettings, {
    defaultSettings: settings
  }))));
}

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select) {
  var _select = select('core/edit-post'),
      isEditorSidebarOpened = _select.isEditorSidebarOpened;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  return {
    settings: getSettings(),
    editorSidebarOpened: isEditorSidebarOpened()
  };
}), (0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      closeGeneralSidebar = _dispatch.closeGeneralSidebar;

  return {
    closeGeneralSidebar: closeGeneralSidebar
  };
})])(BottomSheetSettings);

exports.default = _default;
//# sourceMappingURL=container.native.js.map