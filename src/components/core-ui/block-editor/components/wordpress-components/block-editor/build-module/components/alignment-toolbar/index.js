import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { find } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { ToolbarGroup } from '@wordpress/components';
import { alignLeft, alignRight, alignCenter } from '@wordpress/icons';
var DEFAULT_ALIGNMENT_CONTROLS = [{
  icon: alignLeft,
  title: __('Align text left'),
  align: 'left'
}, {
  icon: alignCenter,
  title: __('Align text center'),
  align: 'center'
}, {
  icon: alignRight,
  title: __('Align text right'),
  align: 'right'
}];
var POPOVER_PROPS = {
  position: 'bottom right',
  isAlternate: true
};
export function AlignmentToolbar(props) {
  var value = props.value,
      onChange = props.onChange,
      _props$alignmentContr = props.alignmentControls,
      alignmentControls = _props$alignmentContr === void 0 ? DEFAULT_ALIGNMENT_CONTROLS : _props$alignmentContr,
      _props$label = props.label,
      label = _props$label === void 0 ? __('Change text alignment') : _props$label,
      _props$isCollapsed = props.isCollapsed,
      isCollapsed = _props$isCollapsed === void 0 ? true : _props$isCollapsed,
      isRTL = props.isRTL;

  function applyOrUnset(align) {
    return function () {
      return onChange(value === align ? undefined : align);
    };
  }

  var activeAlignment = find(alignmentControls, function (control) {
    return control.align === value;
  });

  function setIcon() {
    if (activeAlignment) return activeAlignment.icon;
    return isRTL ? alignRight : alignLeft;
  }

  return createElement(ToolbarGroup, {
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
export default AlignmentToolbar;
//# sourceMappingURL=index.js.map