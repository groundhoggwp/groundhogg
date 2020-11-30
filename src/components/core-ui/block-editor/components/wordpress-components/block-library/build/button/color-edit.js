"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var EMPTY_ARRAY = [];
var isWebPlatform = _element.Platform.OS === 'web';

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
} // The code in this file is copied entirely from the "color" and "style" support flags
// The flag can't be used at the moment because of the extra wrapper around
// the button block markup.


function getBlockDOMNode(clientId) {
  return document.getElementById('block-' + clientId);
}
/**
 * Removed undefined values from nested object.
 *
 * @param {*} object
 * @return {*} Object cleaned from undefined values
 */


var cleanEmptyObject = function cleanEmptyObject(object) {
  if (!(0, _lodash.isObject)(object)) {
    return object;
  }

  var cleanedNestedObjects = (0, _lodash.pickBy)((0, _lodash.mapValues)(object, cleanEmptyObject), _lodash.identity);
  return (0, _lodash.isEqual)(cleanedNestedObjects, {}) ? undefined : cleanedNestedObjects;
};

function ColorPanel(_ref) {
  var settings = _ref.settings,
      clientId = _ref.clientId,
      _ref$enableContrastCh = _ref.enableContrastChecking,
      enableContrastChecking = _ref$enableContrastCh === void 0 ? true : _ref$enableContrastCh;

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      detectedBackgroundColor = _useState2[0],
      setDetectedBackgroundColor = _useState2[1];

  var _useState3 = (0, _element.useState)(),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      detectedColor = _useState4[0],
      setDetectedColor = _useState4[1];

  var title = isWebPlatform ? (0, _i18n.__)('Color settings') : (0, _i18n.__)('Color Settings');
  (0, _element.useEffect)(function () {
    if (isWebPlatform && !enableContrastChecking) {
      return;
    }

    var colorsDetectionElement = getBlockDOMNode(clientId);

    if (!colorsDetectionElement) {
      return;
    }

    setDetectedColor(getComputedStyle(colorsDetectionElement).color);
    var backgroundColorNode = colorsDetectionElement;
    var backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;

    while (backgroundColor === 'rgba(0, 0, 0, 0)' && backgroundColorNode.parentNode && backgroundColorNode.parentNode.nodeType === backgroundColorNode.parentNode.ELEMENT_NODE) {
      backgroundColorNode = backgroundColorNode.parentNode;
      backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;
    }

    setDetectedBackgroundColor(backgroundColor);
  });
  return (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_blockEditor.__experimentalPanelColorGradientSettings, {
    title: title,
    initialOpen: false,
    settings: settings
  }, isWebPlatform && enableContrastChecking && (0, _element.createElement)(_blockEditor.ContrastChecker, {
    backgroundColor: detectedBackgroundColor,
    textColor: detectedColor
  })));
}
/**
 * Inspector control panel containing the color related configuration
 *
 * @param {Object} props
 *
 * @return {WPElement} Color edit element.
 */


function ColorEdit(props) {
  var _style$color2, _style$color3, _style$color4;

  var attributes = props.attributes;
  var colors = (0, _blockEditor.__experimentalUseEditorFeature)('color.palette') || EMPTY_ARRAY;
  var gradients = (0, _blockEditor.__experimentalUseEditorFeature)('color.gradients') || EMPTY_ARRAY; // Shouldn't be needed but right now the ColorGradientsPanel
  // can trigger both onChangeColor and onChangeBackground
  // synchronously causing our two callbacks to override changes
  // from each other.

  var localAttributes = (0, _element.useRef)(attributes);
  (0, _element.useEffect)(function () {
    localAttributes.current = attributes;
  }, [attributes]);
  var style = attributes.style,
      textColor = attributes.textColor,
      backgroundColor = attributes.backgroundColor,
      gradient = attributes.gradient;
  var gradientValue;

  if (gradient) {
    gradientValue = (0, _blockEditor.getGradientValueBySlug)(gradients, gradient);
  } else {
    var _style$color;

    gradientValue = style === null || style === void 0 ? void 0 : (_style$color = style.color) === null || _style$color === void 0 ? void 0 : _style$color.gradient;
  }

  var onChangeColor = function onChangeColor(name) {
    return function (value) {
      var _localAttributes$curr, _localAttributes$curr2;

      var colorObject = (0, _blockEditor.getColorObjectByColorValue)(colors, value);
      var attributeName = name + 'Color';

      var newStyle = _objectSpread(_objectSpread({}, localAttributes.current.style), {}, {
        color: _objectSpread(_objectSpread({}, (_localAttributes$curr = localAttributes.current) === null || _localAttributes$curr === void 0 ? void 0 : (_localAttributes$curr2 = _localAttributes$curr.style) === null || _localAttributes$curr2 === void 0 ? void 0 : _localAttributes$curr2.color), {}, (0, _defineProperty2.default)({}, name, (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) ? undefined : value))
      });

      var newNamedColor = (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) ? colorObject.slug : undefined;
      var newAttributes = (0, _defineProperty2.default)({
        style: cleanEmptyObject(newStyle)
      }, attributeName, newNamedColor);
      props.setAttributes(newAttributes);
      localAttributes.current = _objectSpread(_objectSpread({}, localAttributes.current), newAttributes);
    };
  };

  var onChangeGradient = function onChangeGradient(value) {
    var slug = (0, _blockEditor.getGradientSlugByValue)(gradients, value);
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

  return (0, _element.createElement)(ColorPanel, {
    enableContrastChecking: !gradient && !(style === null || style === void 0 ? void 0 : (_style$color2 = style.color) === null || _style$color2 === void 0 ? void 0 : _style$color2.gradient),
    clientId: props.clientId,
    settings: [{
      label: (0, _i18n.__)('Text Color'),
      onColorChange: onChangeColor('text'),
      colorValue: (0, _blockEditor.getColorObjectByAttributeValues)(colors, textColor, style === null || style === void 0 ? void 0 : (_style$color3 = style.color) === null || _style$color3 === void 0 ? void 0 : _style$color3.text).color
    }, {
      label: (0, _i18n.__)('Background Color'),
      onColorChange: onChangeColor('background'),
      colorValue: (0, _blockEditor.getColorObjectByAttributeValues)(colors, backgroundColor, style === null || style === void 0 ? void 0 : (_style$color4 = style.color) === null || _style$color4 === void 0 ? void 0 : _style$color4.background).color,
      gradientValue: gradientValue,
      onGradientChange: onChangeGradient
    }]
  });
}

var _default = ColorEdit;
exports.default = _default;
//# sourceMappingURL=color-edit.js.map