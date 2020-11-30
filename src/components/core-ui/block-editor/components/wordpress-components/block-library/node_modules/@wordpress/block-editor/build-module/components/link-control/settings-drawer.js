import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl, VisuallyHidden } from '@wordpress/components';
var defaultSettings = [{
  id: 'opensInNewTab',
  title: __('Open in new tab')
}];

var LinkControlSettingsDrawer = function LinkControlSettingsDrawer(_ref) {
  var value = _ref.value,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? noop : _ref$onChange,
      _ref$settings = _ref.settings,
      settings = _ref$settings === void 0 ? defaultSettings : _ref$settings;

  if (!settings || !settings.length) {
    return null;
  }

  var handleSettingChange = function handleSettingChange(setting) {
    return function (newValue) {
      onChange(_objectSpread(_objectSpread({}, value), {}, _defineProperty({}, setting.id, newValue)));
    };
  };

  var theSettings = settings.map(function (setting) {
    return createElement(ToggleControl, {
      className: "block-editor-link-control__setting",
      key: setting.id,
      label: setting.title,
      onChange: handleSettingChange(setting),
      checked: value ? !!value[setting.id] : false
    });
  });
  return createElement("fieldset", {
    className: "block-editor-link-control__settings"
  }, createElement(VisuallyHidden, {
    as: "legend"
  }, __('Currently selected link settings')), theSettings);
};

export default LinkControlSettingsDrawer;
//# sourceMappingURL=settings-drawer.js.map