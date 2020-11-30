import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Platform } from '@wordpress/element';
import { hasBlockSupport } from '@wordpress/blocks';
import { __experimentalBoxControl as BoxControl } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { cleanEmptyObject } from './utils';
import { useCustomUnits } from '../components/unit-control';
export var PADDING_SUPPORT_KEY = '__experimentalPadding';
/**
 * Inspector control panel containing the line height related configuration
 *
 * @param {Object} props
 *
 * @return {WPElement} Line height edit element.
 */

export function PaddingEdit(props) {
  var _style$spacing;

  var blockName = props.name,
      style = props.attributes.style,
      setAttributes = props.setAttributes;
  var units = useCustomUnits();

  if (!hasBlockSupport(blockName, PADDING_SUPPORT_KEY)) {
    return null;
  }

  var onChange = function onChange(next) {
    var newStyle = _objectSpread(_objectSpread({}, style), {}, {
      spacing: {
        padding: next
      }
    });

    setAttributes({
      style: cleanEmptyObject(newStyle)
    });
  };

  var onChangeShowVisualizer = function onChangeShowVisualizer(next) {
    var newStyle = _objectSpread(_objectSpread({}, style), {}, {
      visualizers: {
        padding: next
      }
    });

    setAttributes({
      style: cleanEmptyObject(newStyle)
    });
  };

  return Platform.select({
    web: createElement(Fragment, null, createElement(BoxControl, {
      values: style === null || style === void 0 ? void 0 : (_style$spacing = style.spacing) === null || _style$spacing === void 0 ? void 0 : _style$spacing.padding,
      onChange: onChange,
      onChangeShowVisualizer: onChangeShowVisualizer,
      label: __('Padding'),
      units: units
    })),
    native: null
  });
}
//# sourceMappingURL=padding.js.map