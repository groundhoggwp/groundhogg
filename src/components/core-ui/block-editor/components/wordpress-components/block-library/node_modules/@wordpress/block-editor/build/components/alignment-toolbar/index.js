"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.AlignmentToolbar = AlignmentToolbar;
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var DEFAULT_ALIGNMENT_CONTROLS = [{
  icon: _icons.alignLeft,
  title: (0, _i18n.__)('Align text left'),
  align: 'left'
}, {
  icon: _icons.alignCenter,
  title: (0, _i18n.__)('Align text center'),
  align: 'center'
}, {
  icon: _icons.alignRight,
  title: (0, _i18n.__)('Align text right'),
  align: 'right'
}];
var POPOVER_PROPS = {
  position: 'bottom right',
  isAlternate: true
};

function AlignmentToolbar(props) {
  var value = props.value,
      onChange = props.onChange,
      _props$alignmentContr = props.alignmentControls,
      alignmentControls = _props$alignmentContr === void 0 ? DEFAULT_ALIGNMENT_CONTROLS : _props$alignmentContr,
      _props$label = props.label,
      label = _props$label === void 0 ? (0, _i18n.__)('Change text alignment') : _props$label,
      _props$isCollapsed = props.isCollapsed,
      isCollapsed = _props$isCollapsed === void 0 ? true : _props$isCollapsed,
      isRTL = props.isRTL;

  function applyOrUnset(align) {
    return function () {
      return onChange(value === align ? undefined : align);
    };
  }

  var activeAlignment = (0, _lodash.find)(alignmentControls, function (control) {
    return control.align === value;
  });

  function setIcon() {
    if (activeAlignment) return activeAlignment.icon;
    return isRTL ? _icons.alignRight : _icons.alignLeft;
  }

  return (0, _element.createElement)(_components.ToolbarGroup, {
    isCollapsed: isCollapsed,
    icon: setIcon(),
    label: label,
    popoverProps: POPOVER_PROPS,
    controls: alignmentControls.map(function (control) {
      var align = control.align;
      var isActive = value === align;
      return _objectSpread(_objectSpread({}, control), {}, {
        isActive: isActive,
        role: isCollapsed ? 'menuitemradio' : undefined,
        onClick: applyOrUnset(align)
      });
    })
  });
}

var _default = AlignmentToolbar;
exports.default = _default;
//# sourceMappingURL=index.js.map