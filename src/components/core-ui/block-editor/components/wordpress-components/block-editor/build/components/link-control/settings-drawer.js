"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var defaultSettings = [{
  id: 'opensInNewTab',
  title: (0, _i18n.__)('Open in new tab')
}];

var LinkControlSettingsDrawer = function LinkControlSettingsDrawer(_ref) {
  var value = _ref.value,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$settings = _ref.settings,
      settings = _ref$settings === void 0 ? defaultSettings : _ref$settings;

  if (!settings || !settings.length) {
    return null;
  }

  var handleSettingChange = function handleSettingChange(setting) {
    return function (newValue) {
      onChange(_objectSpread(_objectSpread({}, value), {}, (0, _defineProperty2.default)({}, setting.id, newValue)));
    };
  };

  var theSettings = settings.map(function (setting) {
    return (0, _element.createElement)(_components.ToggleControl, {
      className: "block-editor-link-control__setting",
      key: setting.id,
      label: setting.title,
      onChange: handleSettingChange(setting),
      checked: value ? !!value[setting.id] : false
    });
  });
  return (0, _element.createElement)("fieldset", {
    className: "block-editor-link-control__settings"
  }, (0, _element.createElement)(_components.VisuallyHidden, {
    as: "legend"
  }, (0, _i18n.__)('Currently selected link settings')), theSettings);
};

var _default = LinkControlSettingsDrawer;
exports.default = _default;
//# sourceMappingURL=settings-drawer.js.map