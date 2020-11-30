import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { BottomSheet, ColorSettings } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import styles from './container.native.scss';
export var blockSettingsScreens = {
  settings: 'Settings',
  color: 'Color'
};

function BottomSheetSettings(_ref) {
  var editorSidebarOpened = _ref.editorSidebarOpened,
      closeGeneralSidebar = _ref.closeGeneralSidebar,
      settings = _ref.settings,
      props = _objectWithoutProperties(_ref, ["editorSidebarOpened", "closeGeneralSidebar", "settings"]);

  return createElement(BottomSheet, _extends({
    isVisible: editorSidebarOpened,
    onClose: closeGeneralSidebar,
    hideHeader: true,
    contentStyle: styles.content
  }, props), createElement(BottomSheet.NavigationContainer, {
    animate: true,
    main: true
  }, createElement(BottomSheet.NavigationScreen, {
    name: blockSettingsScreens.settings
  }, createElement(InspectorControls.Slot, null)), createElement(BottomSheet.NavigationScreen, {
    name: blockSettingsScreens.color
  }, createElement(ColorSettings, {
    defaultSettings: settings
  }))));
}

export default compose([withSelect(function (select) {
  var _select = select('core/edit-post'),
      isEditorSidebarOpened = _select.isEditorSidebarOpened;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  return {
    settings: getSettings(),
    editorSidebarOpened: isEditorSidebarOpened()
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      closeGeneralSidebar = _dispatch.closeGeneralSidebar;

  return {
    closeGeneralSidebar: closeGeneralSidebar
  };
})])(BottomSheetSettings);
//# sourceMappingURL=container.native.js.map