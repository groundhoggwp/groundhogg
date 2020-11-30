import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarGroup } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { positionCenter, positionLeft, positionRight, stretchFullWidth, stretchWide } from '@wordpress/icons';
var BLOCK_ALIGNMENTS_CONTROLS = {
  left: {
    icon: positionLeft,
    title: __('Align left')
  },
  center: {
    icon: positionCenter,
    title: __('Align center')
  },
  right: {
    icon: positionRight,
    title: __('Align right')
  },
  wide: {
    icon: stretchWide,
    title: __('Wide width')
  },
  full: {
    icon: stretchFullWidth,
    title: __('Full width')
  }
};
var DEFAULT_CONTROLS = ['left', 'center', 'right', 'wide', 'full'];
var DEFAULT_CONTROL = 'center';
var WIDE_CONTROLS = ['wide', 'full'];
var POPOVER_PROPS = {
  isAlternate: true
};
export function BlockAlignmentToolbar(_ref) {
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
  return createElement(ToolbarGroup, {
    popoverProps: POPOVER_PROPS,
    isCollapsed: isCollapsed,
    icon: activeAlignmentControl ? activeAlignmentControl.icon : defaultAlignmentControl.icon,
    label: __('Change alignment'),
    controls: enabledControls.map(function (control) {
      return _objectSpread(_objectSpread({}, BLOCK_ALIGNMENTS_CONTROLS[control]), {}, {
        isActive: value === control,
        role: isCollapsed ? 'menuitemradio' : undefined,
        onClick: applyOrUnset(control)
      });
    })
  });
}
export default compose(withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  var settings = getSettings();
  return {
    wideControlsEnabled: settings.alignWide
  };
}))(BlockAlignmentToolbar);
//# sourceMappingURL=index.js.map