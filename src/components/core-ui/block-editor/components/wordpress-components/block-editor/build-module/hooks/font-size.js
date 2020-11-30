import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { hasBlockSupport } from '@wordpress/blocks';
import TokenList from '@wordpress/token-list';
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { getFontSize, getFontSizeClass, getFontSizeObjectByValue, FontSizePicker } from '../components/font-sizes';
import { cleanEmptyObject } from './utils';
import useEditorFeature from '../components/use-editor-feature';
export var FONT_SIZE_SUPPORT_KEY = '__experimentalFontSize';
/**
 * Filters registered block settings, extending attributes to include
 * `fontSize` and `fontWeight` attributes.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */

function addAttributes(settings) {
  if (!hasBlockSupport(settings, FONT_SIZE_SUPPORT_KEY)) {
    return settings;
  } // Allow blocks to specify a default value if needed.


  if (!settings.attributes.fontSize) {
    Object.assign(settings.attributes, {
      fontSize: {
        type: 'string'
      }
    });
  }

  return settings;
}
/**
 * Override props assigned to save component to inject font size.
 *
 * @param  {Object} props      Additional props applied to save element
 * @param  {Object} blockType  Block type
 * @param  {Object} attributes Block attributes
 * @return {Object}            Filtered props applied to save element
 */


function addSaveProps(props, blockType, attributes) {
  if (!hasBlockSupport(blockType, FONT_SIZE_SUPPORT_KEY)) {
    return props;
  } // Use TokenList to dedupe classes.


  var classes = new TokenList(props.className);
  classes.add(getFontSizeClass(attributes.fontSize));
  var newClassName = classes.value;
  props.className = newClassName ? newClassName : undefined;
  return props;
}
/**
 * Filters registered block settings to expand the block edit wrapper
 * by applying the desired styles and classnames.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */


function addEditProps(settings) {
  if (!hasBlockSupport(settings, FONT_SIZE_SUPPORT_KEY)) {
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
 * Inspector control panel containing the font size related configuration
 *
 * @param {Object} props
 *
 * @return {WPElement} Font size edit element.
 */


export function FontSizeEdit(props) {
  var _style$typography;

  var _props$attributes = props.attributes,
      fontSize = _props$attributes.fontSize,
      style = _props$attributes.style,
      setAttributes = props.setAttributes;
  var isDisabled = useIsFontSizeDisabled(props);
  var fontSizes = useEditorFeature('typography.fontSizes');

  if (isDisabled) {
    return null;
  }

  var fontSizeObject = getFontSize(fontSizes, fontSize, style === null || style === void 0 ? void 0 : (_style$typography = style.typography) === null || _style$typography === void 0 ? void 0 : _style$typography.fontSize);

  var onChange = function onChange(value) {
    var fontSizeSlug = getFontSizeObjectByValue(fontSizes, value).slug;
    setAttributes({
      style: cleanEmptyObject(_objectSpread(_objectSpread({}, style), {}, {
        typography: _objectSpread(_objectSpread({}, style === null || style === void 0 ? void 0 : style.typography), {}, {
          fontSize: fontSizeSlug ? undefined : value
        })
      })),
      fontSize: fontSizeSlug
    });
  };

  return createElement(FontSizePicker, {
    value: fontSizeObject.size,
    onChange: onChange
  });
}
/**
 * Custom hook that checks if font-size settings have been disabled.
 *
 * @param {string} name The name of the block.
 * @return {boolean} Whether setting is disabled.
 */

export function useIsFontSizeDisabled() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      blockName = _ref.name;

  var fontSizes = useEditorFeature('typography.fontSizes');
  var hasFontSizes = fontSizes.length;
  return !hasBlockSupport(blockName, FONT_SIZE_SUPPORT_KEY) || !hasFontSizes;
}
/**
 * Add inline styles for font sizes.
 * Ideally, this is not needed and themes load the font-size classes on the
 * editor.
 *
 * @param  {Function} BlockListBlock Original component
 * @return {Function}                Wrapped component
 */

var withFontSizeInlineStyles = createHigherOrderComponent(function (BlockListBlock) {
  return function (props) {
    var _style$typography2;

    var fontSizes = useEditorFeature('typography.fontSizes');
    var blockName = props.name,
        _props$attributes2 = props.attributes,
        fontSize = _props$attributes2.fontSize,
        style = _props$attributes2.style,
        wrapperProps = props.wrapperProps;

    var newProps = _objectSpread({}, props); // Only add inline styles if the block supports font sizes, doesn't
    // already have an inline font size, and does have a class to extract
    // the font size from.


    if (hasBlockSupport(blockName, FONT_SIZE_SUPPORT_KEY) && fontSize && !(style === null || style === void 0 ? void 0 : (_style$typography2 = style.typography) === null || _style$typography2 === void 0 ? void 0 : _style$typography2.fontSize)) {
      var _style$typography3;

      var fontSizeValue = getFontSize(fontSizes, fontSize, style === null || style === void 0 ? void 0 : (_style$typography3 = style.typography) === null || _style$typography3 === void 0 ? void 0 : _style$typography3.fontSize).size;
      newProps.wrapperProps = _objectSpread(_objectSpread({}, wrapperProps), {}, {
        style: _objectSpread({
          fontSize: fontSizeValue
        }, wrapperProps === null || wrapperProps === void 0 ? void 0 : wrapperProps.style)
      });
    }

    return createElement(BlockListBlock, newProps);
  };
}, 'withFontSizeInlineStyles');
addFilter('blocks.registerBlockType', 'core/font/addAttribute', addAttributes);
addFilter('blocks.getSaveContent.extraProps', 'core/font/addSaveProps', addSaveProps);
addFilter('blocks.registerBlockType', 'core/font/addEditProps', addEditProps);
addFilter('editor.BlockListBlock', 'core/font-size/with-font-size-inline-styles', withFontSizeInlineStyles);
//# sourceMappingURL=font-size.js.map