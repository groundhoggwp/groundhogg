import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createSlotFill, ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { withDispatch } from '@wordpress/data';
import { cog } from '@wordpress/icons';

var _createSlotFill = createSlotFill('SettingsToolbarButton'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var SettingsButton = function SettingsButton(_ref) {
  var openGeneralSidebar = _ref.openGeneralSidebar;
  return createElement(ToolbarButton, {
    title: __('Open Settings'),
    icon: cog,
    onClick: openGeneralSidebar
  });
};

var SettingsButtonFill = function SettingsButtonFill(props) {
  return createElement(Fill, null, createElement(SettingsButton, props));
};

var SettingsToolbarButton = withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch.openGeneralSidebar;

  return {
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    }
  };
})(SettingsButtonFill);
SettingsToolbarButton.Slot = Slot;
export default SettingsToolbarButton;
//# sourceMappingURL=button.native.js.map