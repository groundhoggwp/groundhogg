"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.createCustomColorsHOC = createCustomColorsHOC;
exports.default = withColors;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _utils = require("./utils");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var DEFAULT_COLORS = [];
/**
 * Higher order component factory for injecting the `colorsArray` argument as
 * the colors prop in the `withCustomColors` HOC.
 *
 * @param {Array} colorsArray An array of color objects.
 *
 * @return {Function} The higher order component.
 */

var withCustomColorPalette = function withCustomColorPalette(colorsArray) {
  return (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
    return function (props) {
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
        colors: colorsArray
      }));
    };
  }, 'withCustomColorPalette');
};
/**
 * Higher order component factory for injecting the editor colors as the
 * `colors` prop in the `withColors` HOC.
 *
 * @return {Function} The higher order component.
 */


var withEditorColorPalette = function withEditorColorPalette() {
  return (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
    return function (props) {
      var colors = (0, _useEditorFeature.default)('color.palette') || DEFAULT_COLORS;
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
        colors: colors
      }));
    };
  }, 'withEditorColorPalette');
};
/**
 * Helper function used with `createHigherOrderComponent` to create
 * higher order components for managing color logic.
 *
 * @param {Array}    colorTypes       An array of color types (e.g. 'backgroundColor, borderColor).
 * @param {Function} withColorPalette A HOC for injecting the 'colors' prop into the WrappedComponent.
 *
 * @return {WPComponent} The component that can be used as a HOC.
 */


function createColorHOC(colorTypes, withColorPalette) {
  var colorMap = (0, _lodash.reduce)(colorTypes, function (colorObject, colorType) {
    return _objectSpread(_objectSpread({}, colorObject), (0, _lodash.isString)(colorType) ? (0, _defineProperty2.default)({}, colorType, (0, _lodash.kebabCase)(colorType)) : colorType);
  }, {});
  return (0, _compose.compose)([withColorPalette, function (WrappedComponent) {
    return /*#__PURE__*/function (_Component) {
      (0, _inherits2.default)(_class, _Component);

      var _super = _createSuper(_class);

      function _class(props) {
        var _this;

        (0, _classCallCheck2.default)(this, _class);
        _this = _super.call(this, props);
        _this.setters = _this.createSetters();
        _this.colorUtils = {
          getMostReadableColor: _this.getMostReadableColor.bind((0, _assertThisInitialized2.default)(_this))
        };
        _this.state = {};
        return _this;
      }

      (0, _createClass2.default)(_class, [{
        key: "getMostReadableColor",
        value: function getMostReadableColor(colorValue) {
          var colors = this.props.colors;
          return (0, _utils.getMostReadableColor)(colors, colorValue);
        }
      }, {
        key: "createSetters",
        value: function createSetters() {
          var _this2 = this;

          return (0, _lodash.reduce)(colorMap, function (settersAccumulator, colorContext, colorAttributeName) {
            var upperFirstColorAttributeName = (0, _lodash.upperFirst)(colorAttributeName);
            var customColorAttributeName = "custom".concat(upperFirstColorAttributeName);
            settersAccumulator["set".concat(upperFirstColorAttributeName)] = _this2.createSetColor(colorAttributeName, customColorAttributeName);
            return settersAccumulator;
          }, {});
        }
      }, {
        key: "createSetColor",
        value: function createSetColor(colorAttributeName, customColorAttributeName) {
          var _this3 = this;

          return function (colorValue) {
            var _this3$props$setAttri;

            var colorObject = (0, _utils.getColorObjectByColorValue)(_this3.props.colors, colorValue);

            _this3.props.setAttributes((_this3$props$setAttri = {}, (0, _defineProperty2.default)(_this3$props$setAttri, colorAttributeName, colorObject && colorObject.slug ? colorObject.slug : undefined), (0, _defineProperty2.default)(_this3$props$setAttri, customColorAttributeName, colorObject && colorObject.slug ? undefined : colorValue), _this3$props$setAttri));
          };
        }
      }, {
        key: "render",
        value: function render() {
          return (0, _element.createElement)(WrappedComponent, _objectSpread(_objectSpread(_objectSpread(_objectSpread({}, this.props), {}, {
            colors: undefined
          }, this.state), this.setters), {}, {
            colorUtils: this.colorUtils
          }));
        }
      }], [{
        key: "getDerivedStateFromProps",
        value: function getDerivedStateFromProps(_ref2, previousState) {
          var attributes = _ref2.attributes,
              colors = _ref2.colors;
          return (0, _lodash.reduce)(colorMap, function (newState, colorContext, colorAttributeName) {
            var colorObject = (0, _utils.getColorObjectByAttributeValues)(colors, attributes[colorAttributeName], attributes["custom".concat((0, _lodash.upperFirst)(colorAttributeName))]);
            var previousColorObject = previousState[colorAttributeName];
            var previousColor = (0, _lodash.get)(previousColorObject, ['color']);
            /**
             * The "and previousColorObject" condition checks that a previous color object was already computed.
             * At the start previousColorObject and colorValue are both equal to undefined
             * bus as previousColorObject does not exist we should compute the object.
             */

            if (previousColor === colorObject.color && previousColorObject) {
              newState[colorAttributeName] = previousColorObject;
            } else {
              newState[colorAttributeName] = _objectSpread(_objectSpread({}, colorObject), {}, {
                class: (0, _utils.getColorClassName)(colorContext, colorObject.slug)
              });
            }

            return newState;
          }, {});
        }
      }]);
      return _class;
    }(_element.Component);
  }]);
}
/**
 * A higher-order component factory for creating a 'withCustomColors' HOC, which handles color logic
 * for class generation color value, retrieval and color attribute setting.
 *
 * Use this higher-order component to work with a custom set of colors.
 *
 * @example
 *
 * ```jsx
 * const CUSTOM_COLORS = [ { name: 'Red', slug: 'red', color: '#ff0000' }, { name: 'Blue', slug: 'blue', color: '#0000ff' } ];
 * const withCustomColors = createCustomColorsHOC( CUSTOM_COLORS );
 * // ...
 * export default compose(
 *     withCustomColors( 'backgroundColor', 'borderColor' ),
 *     MyColorfulComponent,
 * );
 * ```
 *
 * @param {Array} colorsArray The array of color objects (name, slug, color, etc... ).
 *
 * @return {Function} Higher-order component.
 */


function createCustomColorsHOC(colorsArray) {
  return function () {
    var withColorPalette = withCustomColorPalette(colorsArray);

    for (var _len = arguments.length, colorTypes = new Array(_len), _key = 0; _key < _len; _key++) {
      colorTypes[_key] = arguments[_key];
    }

    return (0, _compose.createHigherOrderComponent)(createColorHOC(colorTypes, withColorPalette), 'withCustomColors');
  };
}
/**
 * A higher-order component, which handles color logic for class generation color value, retrieval and color attribute setting.
 *
 * For use with the default editor/theme color palette.
 *
 * @example
 *
 * ```jsx
 * export default compose(
 *     withColors( 'backgroundColor', { textColor: 'color' } ),
 *     MyColorfulComponent,
 * );
 * ```
 *
 * @param {...(Object|string)} colorTypes The arguments can be strings or objects. If the argument is an object,
 *                                        it should contain the color attribute name as key and the color context as value.
 *                                        If the argument is a string the value should be the color attribute name,
 *                                        the color context is computed by applying a kebab case transform to the value.
 *                                        Color context represents the context/place where the color is going to be used.
 *                                        The class name of the color is generated using 'has' followed by the color name
 *                                        and ending with the color context all in kebab case e.g: has-green-background-color.
 *
 * @return {Function} Higher-order component.
 */


function withColors() {
  var withColorPalette = withEditorColorPalette();

  for (var _len2 = arguments.length, colorTypes = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    colorTypes[_key2] = arguments[_key2];
  }

  return (0, _compose.createHigherOrderComponent)(createColorHOC(colorTypes, withColorPalette), 'withColors');
}
//# sourceMappingURL=with-colors.js.map