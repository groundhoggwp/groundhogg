"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockAlignmentToolbar = BlockAlignmentToolbar;
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var BLOCK_ALIGNMENTS_CONTROLS = {
  left: {
    icon: _icons.positionLeft,
    title: (0, _i18n.__)('Align left')
  },
  center: {
    icon: _icons.positionCenter,
    title: (0, _i18n.__)('Align center')
  },
  right: {
    icon: _icons.positionRight,
    title: (0, _i18n.__)('Align right')
  },
  wide: {
    icon: _icons.stretchWide,
    title: (0, _i18n.__)('Wide width')
  },
  full: {
    icon: _icons.stretchFullWidth,
    title: (0, _i18n.__)('Full width')
  }
};
var DEFAULT_CONTROLS = ['left', 'center', 'right', 'wide', 'full'];
var DEFAULT_CONTROL = 'center';
var WIDE_CONTROLS = ['wide', 'full'];
var POPOVER_PROPS = {
  isAlternate: true
};

function BlockAlignmentToolbar(_ref) {
  var value = _ref.value,
      onChange = _ref.onChange,
      _ref$controls = _ref.controls,
      controls = _ref$controls === void 0 ? DEFAULT_CONTROLS : _ref$controls,
      _ref$isCollapsed = _ref.isCollapsed,
      isCollapsed = _ref$isCollapsed === void 0 ? true : _ref$isCollapsed,
      _ref$wideControlsEnab = _ref.wideControlsEnabled,
      wideControlsEnabled = _ref$wideControlsEnab === void 0 ? false : _ref$wideControlsEnab;

  function applyOrUnset(align) {
    return function () {
      return onChange(value === align ? undefined : align);
    };
  }

  var enabledControls = wideControlsEnabled ? controls : controls.filter(function (control) {
    return WIDE_CONTROLS.indexOf(control) === -1;
  });
  var activeAlignmentControl = BLOCK_ALIGNMENTS_CONTROLS[value];
  var defaultAlignmentControl = BLOCK_ALIGNMENTS_CONTROLS[DEFAULT_CONTROL];
  return (0, _element.createElement)(_components.ToolbarGroup, {
    popoverProps: POPOVER_PROPS,
    isCollapsed: isCollapsed,
    icon: activeAlignmentControl ? activeAlignmentControl.icon : defaultAlignmentControl.icon,
    label: (0, _i18n.__)('Change alignment'),
    controls: enabledControls.map(function (control) {
      return _objectSpread(_objectSpread({}, BLOCK_ALIGNMENTS_CONTROLS[control]), {}, {
        isActive: value === control,
        role: isCollapsed ? 'menuitemradio' : undefined,
        onClick: applyOrUnset(control)
      });
    })
  });
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  var settings = getSettings();
  return {
    wideControlsEnabled: settings.alignWide
  };
}))(BlockAlignmentToolbar);

exports.default = _default;
//# sourceMappingURL=index.js.map