import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _toArray from "@babel/runtime/helpers/esm/toArray";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { has, get, startsWith } from 'lodash';
/**
 * WordPress dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { hasBlockSupport, __EXPERIMENTAL_STYLE_PROPERTY as STYLE_PROPERTY } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { COLOR_SUPPORT_KEY, ColorEdit } from './color';
import { TypographyPanel, TYPOGRAPHY_SUPPORT_KEYS } from './typography';
import { PADDING_SUPPORT_KEY, PaddingEdit } from './padding';
import SpacingPanelControl from '../components/spacing-panel-control';
var styleSupportKeys = [].concat(_toConsumableArray(TYPOGRAPHY_SUPPORT_KEYS), [COLOR_SUPPORT_KEY, PADDING_SUPPORT_KEY]);

var hasStyleSupport = function hasStyleSupport(blockType) {
  return styleSupportKeys.some(function (key) {
    return hasBlockSupport(blockType, key);
  });
};

var VARIABLE_REFERENCE_PREFIX = 'var:';
var VARIABLE_PATH_SEPARATOR_TOKEN_ATTRIBUTE = '|';
var VARIABLE_PATH_SEPARATOR_TOKEN_STYLE = '--';

function compileStyleValue(uncompiledValue) {
  if (startsWith(uncompiledValue, VARIABLE_REFERENCE_PREFIX)) {
    var variable = uncompiledValue.slice(VARIABLE_REFERENCE_PREFIX.length).split(VARIABLE_PATH_SEPARATOR_TOKEN_ATTRIBUTE).join(VARIABLE_PATH_SEPARATOR_TOKEN_STYLE);
    return "var(--wp--".concat(variable, ")");
  }

  return uncompiledValue;
}
/**
 * Returns the inline styles to add depending on the style object
 *
 * @param  {Object} styles Styles configuration
 * @return {Object}        Flattened CSS variables declaration
 */


export function getInlineStyles() {
  var styles = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var output = {};
  Object.entries(STYLE_PROPERTY).forEach(function (_ref) {
    var _ref2 = _toArray(_ref),
        styleKey = _ref2[0],
        otherObjectKeys = _ref2.slice(1);

    var _otherObjectKeys = _slicedToArray(otherObjectKeys, 1),
        objectKeys = _otherObjectKeys[0];

    if (has(styles, objectKeys)) {
      output[styleKey] = compileStyleValue(get(styles, objectKeys));
    }
  });
  return output;
}
/**
 * Filters registered block settings, extending attributes to include `style` attribute.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */

function addAttribute(settings) {
  if (!hasStyleSupport(settings)) {
    return settings;
  } // allow blocks to specify their own attribute definition with default values if needed.


  if (!settings.attributes.style) {
    Object.assign(settings.attributes, {
      style: {
        type: 'object'
      }
    });
  }

  return settings;
}
/**
 * Override props assigned to save component to inject the CSS variables definition.
 *
 * @param  {Object} props      Additional props applied to save element
 * @param  {Object} blockType  Block type
 * @param  {Object} attributes Block attributes
 * @return {Object}            Filtered props applied to save element
 */


export function addSaveProps(props, blockType, attributes) {
  if (!hasStyleSupport(blockType)) {
    return props;
  }

  var style = attributes.style;
  props.style = _objectSpread(_objectSpread({}, getInlineStyles(style)), props.style);
  return props;
}
/**
 * Filters registered block settings to extand the block edit wrapper
 * to apply the desired styles and classnames properly.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */

export function addEditProps(settings) {
  if (!hasStyleSupport(settings)) {
    return settings;
  }

  var existingGetEditWrapperProps = settings.getEditWrapperProps;

  settings.getEditWrapperProps = function (attributes) {
    var props = {};

    if (existingGetEditWrapperProps) {
      props = existingGetEditWrapperProps(attributes);
    }

    return addSaveProps(props, settings, attributes);
  };

  return settings;
}
/**
 * Override the default edit UI to include new inspector controls for
 * all the custom styles configs.
 *
 * @param  {Function} BlockEdit Original component
 * @return {Function}           Wrapped component
 */

export var withBlockControls = createHigherOrderComponent(function (BlockEdit) {
  return function (props) {
    var blockName = props.name;
    var hasPaddingSupport = hasBlockSupport(blockName, PADDING_SUPPORT_KEY);
    return [createElement(TypographyPanel, _extends({
      key: "typography"
    }, props)), createElement(ColorEdit, _extends({
      key: "colors"
    }, props)), createElement(BlockEdit, _extends({
      key: "edit"
    }, props)), hasPaddingSupport && createElement(SpacingPanelControl, {
      key: "spacing"
    }, createElement(PaddingEdit, props))];
  };
}, 'withToolbarControls');
addFilter('blocks.registerBlockType', 'core/style/addAttribute', addAttribute);
addFilter('blocks.getSaveContent.extraProps', 'core/style/addSaveProps', addSaveProps);
addFilter('blocks.registerBlockType', 'core/style/addEditProps', addEditProps);
addFilter('editor.BlockEdit', 'core/style/with-block-controls', withBlockControls);
//# sourceMappingURL=style.js.map