import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import useEditorFeature from '../use-editor-feature';
export default createHigherOrderComponent(function (WrappedComponent) {
  return function (props) {
    var colorsFeature = useEditorFeature('color.palette');
    var disableCustomColorsFeature = !useEditorFeature('color.custom');
    var colors = props.colors === undefined ? colorsFeature : props.colors;
    var disableCustomColors = props.disableCustomColors === undefined ? disableCustomColorsFeature : props.disableCustomColors;
    var hasColorsToChoose = !isEmpty(colors) || !disableCustomColors;
    return createElement(WrappedComponent, _objectSpread(_objectSpread({}, props), {}, {
      colors: colors,
      disableCustomColors: disableCustomColors,
      hasColorsToChoose: hasColorsToChoose
    }));
  };
}, 'withColorContext');
//# sourceMappingURL=with-color-context.js.map