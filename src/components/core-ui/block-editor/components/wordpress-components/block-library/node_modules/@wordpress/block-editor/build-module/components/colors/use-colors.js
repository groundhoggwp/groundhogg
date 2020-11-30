import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import memoize from 'memize';
import classnames from 'classnames';
import { kebabCase, camelCase, castArray, startCase, isFunction } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback, useMemo, useEffect, Children, cloneElement, useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import InspectorControls from '../inspector-controls';
import { useBlockEditContext } from '../block-edit';
import ColorPanel from './color-panel';
import useEditorFeature from '../use-editor-feature';

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

var DEFAULT_COLORS = [];
var COMMON_COLOR_LABELS = {
  textColor: __('Text Color'),
  backgroundColor: __('Background Color')
};

var InspectorControlsColorPanel = function InspectorControlsColorPanel(props) {
  return createElement(InspectorControls, null, createElement(ColorPanel, props));
};

export default function __experimentalUseColors(colorConfigs) {
  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
    panelTitle: __('Color settings')
  },
      _ref$panelTitle = _ref.panelTitle,
      panelTitle = _ref$panelTitle === void 0 ? __('Color settings') : _ref$panelTitle,
      colorPanelProps = _ref.colorPanelProps,
      contrastCheckers = _ref.contrastCheckers,
      panelChildren = _ref.panelChildren,
      _ref$colorDetector = _ref.colorDetector;

  _ref$colorDetector = _ref$colorDetector === void 0 ? {} : _ref$colorDetector;
  var targetRef = _ref$colorDetector.targetRef,
      _ref$colorDetector$ba = _ref$colorDetector.backgroundColorTargetRef,
      backgroundColorTargetRef = _ref$colorDetector$ba === void 0 ? targetRef : _ref$colorDetector$ba,
      _ref$colorDetector$te = _ref$colorDetector.textColorTargetRef,
      textColorTargetRef = _ref$colorDetector$te === void 0 ? targetRef : _ref$colorDetector$te;
  var deps = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];

  var _useBlockEditContext = useBlockEditContext(),
      clientId = _useBlockEditContext.clientId;

  var settingsColors = useEditorFeature('color.palette') || DEFAULT_COLORS;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockAttributes = _select.getBlockAttributes;

    return {
      attributes: getBlockAttributes(clientId)
    };
  }, [clientId]),
      attributes = _useSelect.attributes;

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var setAttributes = useCallback(function (newAttributes) {
    return updateBlockAttributes(clientId, newAttributes);
  }, [updateBlockAttributes, clientId]);
  var createComponent = useMemo(function () {
    return memoize(function (name, property, className, color, colorValue, customColor) {
      return function (_ref2) {
        var _classnames;

        var children = _ref2.children,
            _ref2$className = _ref2.className,
            componentClassName = _ref2$className === void 0 ? '' : _ref2$className,
            _ref2$style = _ref2.style,
            componentStyle = _ref2$style === void 0 ? {} : _ref2$style;
        var colorStyle = {};

        if (color) {
          colorStyle = _defineProperty({}, property, colorValue);
        } else if (customColor) {
          colorStyle = _defineProperty({}, property, customColor);
        }

        var extraProps = {
          className: classnames(componentClassName, (_classnames = {}, _defineProperty(_classnames, "has-".concat(kebabCase(color), "-").concat(kebabCase(property)), color), _defineProperty(_classnames, className || "has-".concat(kebabCase(name)), color || customColor), _classnames)),
          style: _objectSpread(_objectSpread({}, colorStyle), componentStyle)
        };

        if (isFunction(children)) {
          return children(extraProps);
        }

        return (// Clone children, setting the style property from the color configuration,
          // if not already set explicitly through props.
          Children.map(children, function (child) {
            return cloneElement(child, {
              className: classnames(child.props.className, extraProps.className),
              style: _objectSpread(_objectSpread({}, extraProps.style), child.props.style || {})
            });
          })
        );
      };
    }, {
      maxSize: colorConfigs.length
    });
  }, [colorConfigs.length]);
  var createSetColor = useMemo(function () {
    return memoize(function (name, colors) {
      return function (newColor) {
        var color = colors.find(function (_color) {
          return _color.color === newColor;
        });
        setAttributes(_defineProperty({}, color ? camelCase("custom ".concat(name)) : name, undefined));
        setAttributes(_defineProperty({}, color ? name : camelCase("custom ".concat(name)), color ? color.slug : newColor));
      };
    }, {
      maxSize: colorConfigs.length
    });
  }, [setAttributes, colorConfigs.length]);

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      detectedBackgroundColor = _useState2[0],
      setDetectedBackgroundColor = _useState2[1];

  var _useState3 = useState(),
      _useState4 = _slicedToArray(_useState3, 2),
      detectedColor = _useState4[0],
      setDetectedColor = _useState4[1];

  useEffect(function () {
    if (!contrastCheckers) {
      return undefined;
    }

    var needsBackgroundColor = false;
    var needsColor = false;

    var _iterator = _createForOfIteratorHelper(castArray(contrastCheckers)),
        _step;

    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var _step$value = _step.value,
            _backgroundColor = _step$value.backgroundColor,
            textColor = _step$value.textColor;

        if (!needsBackgroundColor) {
          needsBackgroundColor = _backgroundColor === true;
        }

        if (!needsColor) {
          needsColor = textColor === true;
        }

        if (needsBackgroundColor && needsColor) {
          break;
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }

    if (needsColor) {
      setDetectedColor(getComputedStyle(textColorTargetRef.current).color);
    }

    if (needsBackgroundColor) {
      var backgroundColorNode = backgroundColorTargetRef.current;
      var backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;

      while (backgroundColor === 'rgba(0, 0, 0, 0)' && backgroundColorNode.parentNode && backgroundColorNode.parentNode.nodeType === backgroundColorNode.parentNode.ELEMENT_NODE) {
        backgroundColorNode = backgroundColorNode.parentNode;
        backgroundColor = getComputedStyle(backgroundColorNode).backgroundColor;
      }

      setDetectedBackgroundColor(backgroundColor);
    }
  }, [colorConfigs.reduce(function (acc, colorConfig) {
    return "".concat(acc, " | ").concat(attributes[colorConfig.name], " | ").concat(attributes[camelCase("custom ".concat(colorConfig.name))]);
  }, '')].concat(_toConsumableArray(deps)));
  return useMemo(function () {
    var colorSettings = {};
    var components = colorConfigs.reduce(function (acc, colorConfig) {
      if (typeof colorConfig === 'string') {
        colorConfig = {
          name: colorConfig
        };
      }

      var _colorConfig$color = _objectSpread(_objectSpread({}, colorConfig), {}, {
        color: attributes[colorConfig.name]
      }),
          name = _colorConfig$color.name,
          _colorConfig$color$pr = _colorConfig$color.property,
          property = _colorConfig$color$pr === void 0 ? name : _colorConfig$color$pr,
          className = _colorConfig$color.className,
          _colorConfig$color$pa = _colorConfig$color.panelLabel,
          panelLabel = _colorConfig$color$pa === void 0 ? colorConfig.label || COMMON_COLOR_LABELS[name] || startCase(name) : _colorConfig$color$pa,
          _colorConfig$color$co = _colorConfig$color.componentName,
          componentName = _colorConfig$color$co === void 0 ? startCase(name).replace(/\s/g, '') : _colorConfig$color$co,
          _colorConfig$color$co2 = _colorConfig$color.color,
          color = _colorConfig$color$co2 === void 0 ? colorConfig.color : _colorConfig$color$co2,
          _colorConfig$color$co3 = _colorConfig$color.colors,
          colors = _colorConfig$color$co3 === void 0 ? settingsColors : _colorConfig$color$co3;

      var customColor = attributes[camelCase("custom ".concat(name))]; // We memoize the non-primitives to avoid unnecessary updates
      // when they are used as props for other components.

      var _color = customColor ? undefined : colors.find(function (__color) {
        return __color.slug === color;
      });

      acc[componentName] = createComponent(name, property, className, color, _color && _color.color, customColor);
      acc[componentName].displayName = componentName;
      acc[componentName].color = customColor ? customColor : _color && _color.color;
      acc[componentName].slug = color;
      acc[componentName].setColor = createSetColor(name, colors);
      colorSettings[componentName] = {
        value: _color ? _color.color : attributes[camelCase("custom ".concat(name))],
        onChange: acc[componentName].setColor,
        label: panelLabel,
        colors: colors
      }; // These settings will be spread over the `colors` in
      // `colorPanelProps`, so we need to unset the key here,
      // if not set to an actual value, to avoid overwriting
      // an actual value in `colorPanelProps`.

      if (!colors) {
        delete colorSettings[componentName].colors;
      }

      return acc;
    }, {});
    var wrappedColorPanelProps = {
      title: panelTitle,
      initialOpen: false,
      colorSettings: colorSettings,
      colorPanelProps: colorPanelProps,
      contrastCheckers: contrastCheckers,
      detectedBackgroundColor: detectedBackgroundColor,
      detectedColor: detectedColor,
      panelChildren: panelChildren
    };
    return _objectSpread(_objectSpread({}, components), {}, {
      ColorPanel: createElement(ColorPanel, wrappedColorPanelProps),
      InspectorControlsColorPanel: createElement(InspectorControlsColorPanel, wrappedColorPanelProps)
    });
  }, [attributes, setAttributes, detectedColor, detectedBackgroundColor].concat(_toConsumableArray(deps)));
}
//# sourceMappingURL=use-colors.js.map