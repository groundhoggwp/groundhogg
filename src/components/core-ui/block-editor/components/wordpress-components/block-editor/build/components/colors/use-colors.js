"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = __experimentalUseColors;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _memize = _interopRequireDefault(require("memize"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _inspectorControls = _interopRequireDefault(require("../inspector-controls"));

var _blockEdit = require("../block-edit");

var _colorPanel = _interopRequireDefault(require("./color-panel"));

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

var DEFAULT_COLORS = [];
var COMMON_COLOR_LABELS = {
  textColor: (0, _i18n.__)('Text Color'),
  backgroundColor: (0, _i18n.__)('Background Color')
};

var InspectorControlsColorPanel = function InspectorControlsColorPanel(props) {
  return (0, _element.createElement)(_inspectorControls.default, null, (0, _element.createElement)(_colorPanel.default, props));
};

function __experimentalUseColors(colorConfigs) {
  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
    panelTitle: (0, _i18n.__)('Color settings')
  },
      _ref$panelTitle = _ref.panelTitle,
      panelTitle = _ref$panelTitle === void 0 ? (0, _i18n.__)('Color settings') : _ref$panelTitle,
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

  var _useBlockEditContext = (0, _blockEdit.useBlockEditContext)(),
      clientId = _useBlockEditContext.clientId;

  var settingsColors = (0, _useEditorFeature.default)('color.palette') || DEFAULT_COLORS;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockAttributes = _select.getBlockAttributes;

    return {
      attributes: getBlockAttributes(clientId)
    };
  }, [clientId]),
      attributes = _useSelect.attributes;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var setAttributes = (0, _element.useCallback)(function (newAttributes) {
    return updateBlockAttributes(clientId, newAttributes);
  }, [updateBlockAttributes, clientId]);
  var createComponent = (0, _element.useMemo)(function () {
    return (0, _memize.default)(function (name, property, className, color, colorValue, customColor) {
      return function (_ref2) {
        var _classnames;

        var children = _ref2.children,
            _ref2$className = _ref2.className,
            componentClassName = _ref2$className === void 0 ? '' : _ref2$className,
            _ref2$style = _ref2.style,
            componentStyle = _ref2$style === void 0 ? {} : _ref2$style;
        var colorStyle = {};

        if (color) {
          colorStyle = (0, _defineProperty2.default)({}, property, colorValue);
        } else if (customColor) {
          colorStyle = (0, _defineProperty2.default)({}, property, customColor);
        }

        var extraProps = {
          className: (0, _classnames2.default)(componentClassName, (_classnames = {}, (0, _defineProperty2.default)(_classnames, "has-".concat((0, _lodash.kebabCase)(color), "-").concat((0, _lodash.kebabCase)(property)), color), (0, _defineProperty2.default)(_classnames, className || "has-".concat((0, _lodash.kebabCase)(name)), color || customColor), _classnames)),
          style: _objectSpread(_objectSpread({}, colorStyle), componentStyle)
        };

        if ((0, _lodash.isFunction)(children)) {
          return children(extraProps);
        }

        return (// Clone children, setting the style property from the color configuration,
          // if not already set explicitly through props.
          _element.Children.map(children, function (child) {
            return (0, _element.cloneElement)(child, {
              className: (0, _classnames2.default)(child.props.className, extraProps.className),
              style: _objectSpread(_objectSpread({}, extraProps.style), child.props.style || {})
            });
          })
        );
      };
    }, {
      maxSize: colorConfigs.length
    });
  }, [colorConfigs.length]);
  var createSetColor = (0, _element.useMemo)(function () {
    return (0, _memize.default)(function (name, colors) {
      return function (newColor) {
        var color = colors.find(function (_color) {
          return _color.color === newColor;
        });
        setAttributes((0, _defineProperty2.default)({}, color ? (0, _lodash.camelCase)("custom ".concat(name)) : name, undefined));
        setAttributes((0, _defineProperty2.default)({}, color ? name : (0, _lodash.camelCase)("custom ".concat(name)), color ? color.slug : newColor));
      };
    }, {
      maxSize: colorConfigs.length
    });
  }, [setAttributes, colorConfigs.length]);

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      detectedBackgroundColor = _useState2[0],
      setDetectedBackgroundColor = _useState2[1];

  var _useState3 = (0, _element.useState)(),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      detectedColor = _useState4[0],
      setDetectedColor = _useState4[1];

  (0, _element.useEffect)(function () {
    if (!contrastCheckers) {
      return undefined;
    }

    var needsBackgroundColor = false;
    var needsColor = false;

    var _iterator = _createForOfIteratorHelper((0, _lodash.castArray)(contrastCheckers)),
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
    return "".concat(acc, " | ").concat(attributes[colorConfig.name], " | ").concat(attributes[(0, _lodash.camelCase)("custom ".concat(colorConfig.name))]);
  }, '')].concat((0, _toConsumableArray2.default)(deps)));
  return (0, _element.useMemo)(function () {
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
          panelLabel = _colorConfig$color$pa === void 0 ? colorConfig.label || COMMON_COLOR_LABELS[name] || (0, _lodash.startCase)(name) : _colorConfig$color$pa,
          _colorConfig$color$co = _colorConfig$color.componentName,
          componentName = _colorConfig$color$co === void 0 ? (0, _lodash.startCase)(name).replace(/\s/g, '') : _colorConfig$color$co,
          _colorConfig$color$co2 = _colorConfig$color.color,
          color = _colorConfig$color$co2 === void 0 ? colorConfig.color : _colorConfig$color$co2,
          _colorConfig$color$co3 = _colorConfig$color.colors,
          colors = _colorConfig$color$co3 === void 0 ? settingsColors : _colorConfig$color$co3;

      var customColor = attributes[(0, _lodash.camelCase)("custom ".concat(name))]; // We memoize the non-primitives to avoid unnecessary updates
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
        value: _color ? _color.color : attributes[(0, _lodash.camelCase)("custom ".concat(name))],
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
      ColorPanel: (0, _element.createElement)(_colorPanel.default, wrappedColorPanelProps),
      InspectorControlsColorPanel: (0, _element.createElement)(InspectorControlsColorPanel, wrappedColorPanelProps)
    });
  }, [attributes, setAttributes, detectedColor, detectedBackgroundColor].concat((0, _toConsumableArray2.default)(deps)));
}
//# sourceMappingURL=use-colors.js.map