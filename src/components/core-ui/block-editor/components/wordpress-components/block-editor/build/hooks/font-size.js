"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.FontSizeEdit = FontSizeEdit;
exports.useIsFontSizeDisabled = useIsFontSizeDisabled;
exports.FONT_SIZE_SUPPORT_KEY = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _hooks = require("@wordpress/hooks");

var _blocks = require("@wordpress/blocks");

var _tokenList = _interopRequireDefault(require("@wordpress/token-list"));

var _compose = require("@wordpress/compose");

var _fontSizes = require("../components/font-sizes");

var _utils = require("./utils");

var _useEditorFeature = _interopRequireDefault(require("../components/use-editor-feature"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var FONT_SIZE_SUPPORT_KEY = '__experimentalFontSize';
/**
 * Filters registered block settings, extending attributes to include
 * `fontSize` and `fontWeight` attributes.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */

exports.FONT_SIZE_SUPPORT_KEY = FONT_SIZE_SUPPORT_KEY;

function addAttributes(settings) {
  if (!(0, _blocks.hasBlockSupport)(settings, FONT_SIZE_SUPPORT_KEY)) {
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
  if (!(0, _blocks.hasBlockSupport)(blockType, FONT_SIZE_SUPPORT_KEY)) {
    return props;
  } // Use TokenList to dedupe classes.


  var classes = new _tokenList.default(props.className);
  classes.add((0, _fontSizes.getFontSizeClass)(attributes.fontSize));
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
  if (!(0, _blocks.hasBlockSupport)(settings, FONT_SIZE_SUPPORT_KEY)) {
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


function FontSizeEdit(props) {
  var _style$typography;

  var _props$attributes = props.attributes,
      fontSize = _props$attributes.fontSize,
      style = _props$attributes.style,
      setAttributes = props.setAttributes;
  var isDisabled = useIsFontSizeDisabled(props);
  var fontSizes = (0, _useEditorFeature.default)('typography.fontSizes');

  if (isDisabled) {
    return null;
  }

  var fontSizeObject = (0, _fontSizes.getFontSize)(fontSizes, fontSize, style === null || style === void 0 ? void 0 : (_style$typography = style.typography) === null || _style$typography === void 0 ? void 0 : _style$typography.fontSize);

  var onChange = function onChange(value) {
    var fontSizeSlug = (0, _fontSizes.getFontSizeObjectByValue)(fontSizes, value).slug;
    setAttributes({
      style: (0, _utils.cleanEmptyObject)(_objectSpread(_objectSpread({}, style), {}, {
        typography: _objectSpread(_objectSpread({}, style === null || style === void 0 ? void 0 : style.typography), {}, {
          fontSize: fontSizeSlug ? undefined : value
        })
      })),
      fontSize: fontSizeSlug
    });
  };

  return (0, _element.createElement)(_fontSizes.FontSizePicker, {
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


function useIsFontSizeDisabled() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      blockName = _ref.name;

  var fontSizes = (0, _useEditorFeature.default)('typography.fontSizes');
  var hasFontSizes = fontSizes.length;
  return !(0, _blocks.hasBlockSupport)(blockName, FONT_SIZE_SUPPORT_KEY) || !hasFontSizes;
}
/**
 * Add inline styles for font sizes.
 * Ideally, this is not needed and themes load the font-size classes on the
 * editor.
 *
 * @param  {Function} BlockListBlock Original component
 * @return {Function}                Wrapped component
 */


var withFontSizeInlineStyles = (0, _compose.createHigherOrderComponent)(function (BlockListBlock) {
  return function (props) {
    var _style$typography2;

    var fontSizes = (0, _useEditorFeature.default)('typography.fontSizes');
    var blockName = props.name,
        _props$attributes2 = props.attributes,
        fontSize = _props$attributes2.fontSize,
        style = _props$attributes2.style,
        wrapperProps = props.wrapperProps;

    var newProps = _objectSpread({}, props); // Only add inline styles if the block supports font sizes, doesn't
    // already have an inline font size, and does have a class to extract
    // the font size from.


    if ((0, _blocks.hasBlockSupport)(blockName, FONT_SIZE_SUPPORT_KEY) && fontSize && !(style === null || style === void 0 ? void 0 : (_style$typography2 = style.typography) === null || _style$typography2 === void 0 ? void 0 : _style$typography2.fontSize)) {
      var _style$typography3;

      var fontSizeValue = (0, _fontSizes.getFontSize)(fontSizes, fontSize, style === null || style === void 0 ? void 0 : (_style$typography3 = style.typography) === null || _style$typography3 === void 0 ? void 0 : _style$typography3.fontSize).size;
      newProps.wrapperProps = _objectSpread(_objectSpread({}, wrapperProps), {}, {
        style: _objectSpread({
          fontSize: fontSizeValue
        }, wrapperProps === null || wrapperProps === void 0 ? void 0 : wrapperProps.style)
      });
    }

    return (0, _element.createElement)(BlockListBlock, newProps);
  };
}, 'withFontSizeInlineStyles');
(0, _hooks.addFilter)('blocks.registerBlockType', 'core/font/addAttribute', addAttributes);
(0, _hooks.addFilter)('blocks.getSaveContent.extraProps', 'core/font/addSaveProps', addSaveProps);
(0, _hooks.addFilter)('blocks.registerBlockType', 'core/font/addEditProps', addEditProps);
(0, _hooks.addFilter)('editor.BlockListBlock', 'core/font-size/with-font-size-inline-styles', withFontSizeInlineStyles);
//# sourceMappingURL=font-size.js.map