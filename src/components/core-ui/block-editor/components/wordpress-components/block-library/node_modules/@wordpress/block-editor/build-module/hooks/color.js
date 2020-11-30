import _extends from "@babel/runtime/helpers/esm/extends";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { isObject } from 'lodash';
/**
 * WordPress dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { getBlockSupport } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useRef, useEffect, Platform } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { getColorClassName, getColorObjectByColorValue, getColorObjectByAttributeValues } from '../components/colors';
import { __experimentalGetGradientClass, getGradientValueBySlug, getGradientSlugByValue } from '../components/gradients';
import { cleanEmptyObject } from './utils';
import ColorPanel from './color-panel';
import useEditorFeature from '../components/use-editor-feature';
export var COLOR_SUPPORT_KEY = '__experimentalColor';
var EMPTY_ARRAY = [];

var hasColorSupport = function hasColorSupport(blockType) {
  if (Platform.OS !== 'web') {
    return false;
  }

  var colorSupport = getBlockSupport(blockType, COLOR_SUPPORT_KEY);
  return colorSupport && (colorSupport.linkColor === true || colorSupport.gradient === true || colorSupport.background !== false || colorSupport.text !== false);
};

var hasLinkColorSupport = function hasLinkColorSupport(blockType) {
  if (Platform.OS !== 'web') {
    return false;
  }

  var colorSupport = getBlockSupport(blockType, COLOR_SUPPORT_KEY);
  return isObject(colorSupport) && !!colorSupport.linkColor;
};

var hasGradientSupport = function hasGradientSupport(blockType) {
  if (Platform.OS !== 'web') {
    return false;
  }

  var colorSupport = getBlockSupport(blockType, COLOR_SUPPORT_KEY);
  return isObject(colorSupport) && !!colorSupport.gradients;
};

var hasBackgroundColorSupport = function hasBackgroundColorSupport(blockType) {
  if (Platform.OS !== 'web') {
    return false;
  }

  var colorSupport = getBlockSupport(blockType, COLOR_SUPPORT_KEY);
  return colorSupport && colorSupport.background !== false;
};

var hasTextColorSupport = function hasTextColorSupport(blockType) {
  if (Platform.OS !== 'web') {
    return false;
  }

  var colorSupport = getBlockSupport(blockType, COLOR_SUPPORT_KEY);
  return colorSupport && colorSupport.text !== false;
};
/**
 * Filters registered block settings, extending attributes to include
 * `backgroundColor` and `textColor` attribute.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */


function addAttributes(settings) {
  if (!hasColorSupport(settings)) {
    return settings;
  } // allow blocks to specify their own attribute definition with default values if needed.


  if (!settings.attributes.backgroundColor) {
    Object.assign(settings.attributes, {
      backgroundColor: {
        type: 'string'
      }
    });
  }

  if (!settings.attributes.textColor) {
    Object.assign(settings.attributes, {
      textColor: {
        type: 'string'
      }
    });
  }

  if (hasGradientSupport(settings) && !settings.attributes.gradient) {
    Object.assign(settings.attributes, {
      gradient: {
        type: 'string'
      }
    });
  }

  return settings;
}
/**
 * Override props assigned to save component to inject colors classnames.
 *
 * @param  {Object} props      Additional props applied to save element
 * @param  {Object} blockType  Block type
 * @param  {Object} attributes Block attributes
 * @return {Object}            Filtered props applied to save element
 */


export function addSaveProps(props, blockType, attributes) {
  var _style$color, _style$color2, _style$color3, _style$color4, _style$color5, _classnames;

  if (!hasColorSupport(blockType)) {
    return props;
  }

  var hasGradient = hasGradientSupport(blockType); // I'd have prefered to avoid the "style" attribute usage here

  var backgroundColor = attributes.backgroundColor,
      textColor = attributes.textColor,
      gradient = attributes.gradient,
      style = attributes.style;
  var backgroundClass = getColorClassName('background-color', backgroundColor);

  var gradientClass = __experimentalGetGradientClass(gradient);

  var textClass = getColorClassName('color', textColor);
  var newClassName = classnames(props.className, textClass, gradientClass, (_classnames = {}, _defineProperty(_classnames, backgroundClass, (!hasGradient || !(style === null || style === void 0 ? void 0 : (_style$color = style.color) === null || _style$color === void 0 ? void 0 : _style$color.gradient)) && !!backgroundClass), _defineProperty(_classnames, 'has-text-color', textColor || (style === null || style === void 0 ? void 0 : (_style$color2 = style.color) === null || _style$color2 === void 0 ? void 0 : _style$color2.text)), _defineProperty(_classnames, 'has-background', backgroundColor || (style === null || style === void 0 ? void 0 : (_style$color3 = style.color) === null || _style$color3 === void 0 ? void 0 : _style$color3.background) || hasGradient && (gradient || (style === null || style === void 0 ? void 0 : (_style$color4 = style.color) === null || _style$color4 === void 0 ? void 0 : _style$color4.gradient))), _defineProperty(_classnames, 'has-link-color', style === null || style === void 0 ? void 0 : (_style$color5 = style.color) === null || _style$color5 === void 0 ? void 0 : _style$color5.link), _classnames));
  props.className = newClassName ? newClassName : undefined;
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
  if (!hasColorSupport(settings)) {
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

var getLinkColorFromAttributeValue = function getLinkColorFromAttributeValue(colors, value) {
  var attributeParsed = /var:preset\|color\|(.+)/.exec(value);

  if (attributeParsed && attributeParsed[1]) {
    return getColorObjectByAttributeValues(colors, attributeParsed[1]).color;
  }

  return value;
};
/**
 * Inspector control panel containing the color related configuration
 *
 * @param {Object} props
 *
 * @return {WPElement} Color edit element.
 */


export function ColorEdit(props) {
  var _style$color7, _style$color8, _style$color9, _style$color10, _props$attributes$sty2, _props$attributes$sty3;

  var blockName = props.name,
      attributes = props.attributes;
  var isLinkColorEnabled = useEditorFeature('color.link');
  var colors = useEditorFeature('color.palette') || EMPTY_ARRAY;
  var gradients = useEditorFeature('color.gradients') || EMPTY_ARRAY; // Shouldn't be needed but right now the ColorGradientsPanel
  // can trigger both onChangeColor and onChangeBackground
  // synchronously causing our two callbacks to override changes
  // from each other.

  var localAttributes = useRef(attributes);
  useEffect(function () {
    localAttributes.current = attributes;
  }, [attributes]);

  if (!hasColorSupport(blockName)) {
    return null;
  }

  var hasBackground = hasBackgroundColorSupport(blockName);
  var hasGradient = hasGradientSupport(blockName);
  var style = attributes.style,
      textColor = attributes.textColor,
      backgroundColor = attributes.backgroundColor,
      gradient = attributes.gradient;
  var gradientValue;

  if (hasGradient && gradient) {
    gradientValue = getGradientValueBySlug(gradients, gradient);
  } else if (hasGradient) {
    var _style$color6;

    gradientValue = style === null || style === void 0 ? void 0 : (_style$color6 = style.color) === null || _style$color6 === void 0 ? void 0 : _style$color6.gradient;
  }

  var onChangeColor = function onChangeColor(name) {
    return function (value) {
      var _localAttributes$curr, _localAttributes$curr2;

      var colorObject = getColorObjectByColorValue(colors, value);
      var attributeName = name + 'Color';

      var newStyle = _objectSpread(_objectSpread({}, localAttributes.current.style), {}, {
        color: _objectSpread(_objectSpread({}, (_localAttributes$curr = localAttributes.current) === null || _localAttributes$curr === void 0 ? void 0 : (_localAttributes$curr2 = _localAttributes$curr.style) === null || _localAttributes$curr2 === void 0 ? void 0 : _localAttributes$curr2.color), {}, _defineProperty({}, name, (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) ? undefined : value))
      });

      var newNamedColor = (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) ? colorObject.slug : undefined;

      var newAttributes = _defineProperty({
        style: cleanEmptyObject(newStyle)
      }, attributeName, newNamedColor);

      props.setAttributes(newAttributes);
      localAttributes.current = _objectSpread(_objectSpread({}, localAttributes.current), newAttributes);
    };
  };

  var onChangeGradient = function onChangeGradient(value) {
    var slug = getGradientSlugByValue(gradients, value);
    var newAttributes;

    if (slug) {
      var _localAttributes$curr3, _localAttributes$curr4, _localAttributes$curr5;

      var newStyle = _objectSpread(_objectSpread({}, (_localAttributes$curr3 = localAttributes.current) === null || _localAttributes$curr3 === void 0 ? void 0 : _localAttributes$curr3.style), {}, {
        color: _objectSpread(_objectSpread({}, (_localAttributes$curr4 = localAttributes.current) === null || _localAttributes$curr4 === void 0 ? void 0 : (_localAttributes$curr5 = _localAttributes$curr4.style) === null || _localAttributes$curr5 === void 0 ? void 0 : _localAttributes$curr5.color), {}, {
          gradient: undefined
        })
      });

      newAttributes = {
        style: cleanEmptyObject(newStyle),
        gradient: slug
      };
    } else {
      var _localAttributes$curr6, _localAttributes$curr7, _localAttributes$curr8;

      var _newStyle = _objectSpread(_objectSpread({}, (_localAttributes$curr6 = localAttributes.current) === null || _localAttributes$curr6 === void 0 ? void 0 : _localAttributes$curr6.style), {}, {
        color: _objectSpread(_objectSpread({}, (_localAttributes$curr7 = localAttributes.current) === null || _localAttributes$curr7 === void 0 ? void 0 : (_localAttributes$curr8 = _localAttributes$curr7.style) === null || _localAttributes$curr8 === void 0 ? void 0 : _localAttributes$curr8.color), {}, {
          gradient: value
        })
      });

      newAttributes = {
        style: cleanEmptyObject(_newStyle),
        gradient: undefined
      };
    }

    props.setAttributes(newAttributes);
    localAttributes.current = _objectSpread(_objectSpread({}, localAttributes.current), newAttributes);
  };

  var onChangeLinkColor = function onChangeLinkColor(value) {
    var _props$attributes$sty;

    var colorObject = getColorObjectByColorValue(colors, value);
    props.setAttributes({
      style: _objectSpread(_objectSpread({}, props.attributes.style), {}, {
        color: _objectSpread(_objectSpread({}, (_props$attributes$sty = props.attributes.style) === null || _props$attributes$sty === void 0 ? void 0 : _props$attributes$sty.color), {}, {
          link: (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) ? "var:preset|color|".concat(colorObject.slug) : value
        })
      })
    });
  };

  return createElement(ColorPanel, {
    enableContrastChecking: // Turn on contrast checker for web only since it's not supported on mobile yet.
    Platform.OS === 'web' && !gradient && !(style === null || style === void 0 ? void 0 : (_style$color7 = style.color) === null || _style$color7 === void 0 ? void 0 : _style$color7.gradient),
    clientId: props.clientId,
    settings: [].concat(_toConsumableArray(hasTextColorSupport(blockName) ? [{
      label: __('Text Color'),
      onColorChange: onChangeColor('text'),
      colorValue: getColorObjectByAttributeValues(colors, textColor, style === null || style === void 0 ? void 0 : (_style$color8 = style.color) === null || _style$color8 === void 0 ? void 0 : _style$color8.text).color
    }] : []), _toConsumableArray(hasBackground || hasGradient ? [{
      label: __('Background Color'),
      onColorChange: hasBackground ? onChangeColor('background') : undefined,
      colorValue: getColorObjectByAttributeValues(colors, backgroundColor, style === null || style === void 0 ? void 0 : (_style$color9 = style.color) === null || _style$color9 === void 0 ? void 0 : _style$color9.background).color,
      gradientValue: gradientValue,
      onGradientChange: hasGradient ? onChangeGradient : undefined
    }] : []), _toConsumableArray(isLinkColorEnabled && hasLinkColorSupport(blockName) ? [{
      label: __('Link Color'),
      onColorChange: onChangeLinkColor,
      colorValue: getLinkColorFromAttributeValue(colors, style === null || style === void 0 ? void 0 : (_style$color10 = style.color) === null || _style$color10 === void 0 ? void 0 : _style$color10.link),
      clearable: !!((_props$attributes$sty2 = props.attributes.style) === null || _props$attributes$sty2 === void 0 ? void 0 : (_props$attributes$sty3 = _props$attributes$sty2.color) === null || _props$attributes$sty3 === void 0 ? void 0 : _props$attributes$sty3.link)
    }] : []))
  });
}
/**
 * This adds inline styles for color palette colors.
 * Ideally, this is not needed and themes should load their palettes on the editor.
 *
 * @param  {Function} BlockListBlock Original component
 * @return {Function}                Wrapped component
 */

export var withColorPaletteStyles = createHigherOrderComponent(function (BlockListBlock) {
  return function (props) {
    var _getColorObjectByAttr, _getColorObjectByAttr2, _props$wrapperProps;

    var name = props.name,
        attributes = props.attributes;
    var backgroundColor = attributes.backgroundColor,
        textColor = attributes.textColor;
    var colors = useEditorFeature('color.palette') || EMPTY_ARRAY;

    if (!hasColorSupport(name)) {
      return createElement(BlockListBlock, props);
    }

    var extraStyles = {
      color: textColor ? (_getColorObjectByAttr = getColorObjectByAttributeValues(colors, textColor)) === null || _getColorObjectByAttr === void 0 ? void 0 : _getColorObjectByAttr.color : undefined,
      backgroundColor: backgroundColor ? (_getColorObjectByAttr2 = getColorObjectByAttributeValues(colors, backgroundColor)) === null || _getColorObjectByAttr2 === void 0 ? void 0 : _getColorObjectByAttr2.color : undefined
    };
    var wrapperProps = props.wrapperProps;
    wrapperProps = _objectSpread(_objectSpread({}, props.wrapperProps), {}, {
      style: _objectSpread(_objectSpread({}, extraStyles), (_props$wrapperProps = props.wrapperProps) === null || _props$wrapperProps === void 0 ? void 0 : _props$wrapperProps.style)
    });
    return createElement(BlockListBlock, _extends({}, props, {
      wrapperProps: wrapperProps
    }));
  };
});
addFilter('blocks.registerBlockType', 'core/color/addAttribute', addAttributes);
addFilter('blocks.getSaveContent.extraProps', 'core/color/addSaveProps', addSaveProps);
addFilter('blocks.registerBlockType', 'core/color/addEditProps', addEditProps);
addFilter('editor.BlockListBlock', 'core/color/with-color-palette-styles', withColorPaletteStyles);
//# sourceMappingURL=color.js.map