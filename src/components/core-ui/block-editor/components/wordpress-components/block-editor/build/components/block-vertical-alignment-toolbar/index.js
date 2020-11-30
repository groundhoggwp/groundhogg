"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockVerticalAlignmentToolbar = BlockVerticalAlignmentToolbar;
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("./icons");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var BLOCK_ALIGNMENTS_CONTROLS = {
  top: {
    icon: _icons.alignTop,
    title: (0, _i18n._x)('Vertically Align Top', 'Block vertical alignment setting')
  },
  center: {
    icon: _icons.alignCenter,
    title: (0, _i18n._x)('Vertically Align Middle', 'Block vertical alignment setting')
  },
  bottom: {
    icon: _icons.alignBottom,
    title: (0, _i18n._x)('Vertically Align Bottom', 'Block vertical alignment setting')
  }
};
var DEFAULT_CONTROLS = ['top', 'center', 'bottom'];
var DEFAULT_CONTROL = 'top';
var POPOVER_PROPS = {
  isAlternate: true
};

function BlockVerticalAlignmentToolbar(_ref) {
  var value = _ref.value,
      onChange = _ref.onChange,
      _ref$controls = _ref.controls,
      controls = _ref$controls === void 0 ? DEFAULT_CONTROLS : _ref$controls,
      _ref$isCollapsed = _ref.isCollapsed,
      isCollapsed = _ref$isCollapsed === void 0 ? true : _ref$isCollapsed;

  function applyOrUnset(align) {
    return function () {
      return onChange(value === align ? undefined : align);
    };
  }

  var activeAlignment = BLOCK_ALIGNMENTS_CONTROLS[value];
  var defaultAlignmentControl = BLOCK_ALIGNMENTS_CONTROLS[DEFAULT_CONTROL];
  return (0, _element.createElement)(_components.ToolbarGroup, {
    popoverProps: POPOVER_PROPS,
    isCollapsed: isCollapsed,
    icon: activeAlignment ? activeAlignment.icon : defaultAlignmentControl.icon,
    label: (0, _i18n._x)('Change vertical alignment', 'Block vertical alignment setting label'),
    controls: controls.map(function (control) {
      return _objectSpread(_objectSpread({}, BLOCK_ALIGNMENTS_CONTROLS[control]), {}, {
        isActive: value === control,
        role: isCollapsed ? 'menuitemradio' : undefined,
        onClick: applyOrUnset(control)
      });
    })
  });
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-vertical-alignment-toolbar/README.md
 */


var _default = BlockVerticalAlignmentToolbar;
exports.default = _default;
//# sourceMappingURL=index.js.map