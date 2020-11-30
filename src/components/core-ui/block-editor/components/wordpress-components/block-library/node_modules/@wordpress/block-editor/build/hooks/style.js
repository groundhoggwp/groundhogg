"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getInlineStyles = getInlineStyles;
exports.addSaveProps = addSaveProps;
exports.addEditProps = addEditProps;
exports.withBlockControls = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _toArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toArray"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _blocks = require("@wordpress/blocks");

var _compose = require("@wordpress/compose");

var _color = require("./color");

var _typography = require("./typography");

var _padding = require("./padding");

var _spacingPanelControl = _interopRequireDefault(require("../components/spacing-panel-control"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var styleSupportKeys = [].concat((0, _toConsumableArray2.default)(_typography.TYPOGRAPHY_SUPPORT_KEYS), [_color.COLOR_SUPPORT_KEY, _padding.PADDING_SUPPORT_KEY]);

var hasStyleSupport = function hasStyleSupport(blockType) {
  return styleSupportKeys.some(function (key) {
    return (0, _blocks.hasBlockSupport)(blockType, key);
  });
};

var VARIABLE_REFERENCE_PREFIX = 'var:';
var VARIABLE_PATH_SEPARATOR_TOKEN_ATTRIBUTE = '|';
var VARIABLE_PATH_SEPARATOR_TOKEN_STYLE = '--';

function compileStyleValue(uncompiledValue) {
  if ((0, _lodash.startsWith)(uncompiledValue, VARIABLE_REFERENCE_PREFIX)) {
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


function getInlineStyles() {
  var styles = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var output = {};
  Object.entries(_blocks.__EXPERIMENTAL_STYLE_PROPERTY).forEach(function (_ref) {
    var _ref2 = (0, _toArray2.default)(_ref),
        styleKey = _ref2[0],
        otherObjectKeys = _ref2.slice(1);

    var _otherObjectKeys = (0, _slicedToArray2.default)(otherObjectKeys, 1),
        objectKeys = _otherObjectKeys[0];

    if ((0, _lodash.has)(styles, objectKeys)) {
      output[styleKey] = compileStyleValue((0, _lodash.get)(styles, objectKeys));
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


function addSaveProps(props, blockType, attributes) {
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


function addEditProps(settings) {
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


var withBlockControls = (0, _compose.createHigherOrderComponent)(function (BlockEdit) {
  return function (props) {
    var blockName = props.name;
    var hasPaddingSupport = (0, _blocks.hasBlockSupport)(blockName, _padding.PADDING_SUPPORT_KEY);
    return [(0, _element.createElement)(_typography.TypographyPanel, (0, _extends2.default)({
      key: "typography"
    }, props)), (0, _element.createElement)(_color.ColorEdit, (0, _extends2.default)({
      key: "colors"
    }, props)), (0, _element.createElement)(BlockEdit, (0, _extends2.default)({
      key: "edit"
    }, props)), hasPaddingSupport && (0, _element.createElement)(_spacingPanelControl.default, {
      key: "spacing"
    }, (0, _element.createElement)(_padding.PaddingEdit, props))];
  };
}, 'withToolbarControls');
exports.withBlockControls = withBlockControls;
(0, _hooks.addFilter)('blocks.registerBlockType', 'core/style/addAttribute', addAttribute);
(0, _hooks.addFilter)('blocks.getSaveContent.extraProps', 'core/style/addSaveProps', addSaveProps);
(0, _hooks.addFilter)('blocks.registerBlockType', 'core/style/addEditProps', addEditProps);
(0, _hooks.addFilter)('editor.BlockEdit', 'core/style/with-block-controls', withBlockControls);
//# sourceMappingURL=style.js.map