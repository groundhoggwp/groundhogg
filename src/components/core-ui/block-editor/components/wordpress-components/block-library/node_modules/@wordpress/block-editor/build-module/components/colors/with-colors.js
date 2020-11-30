import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { get, isString, kebabCase, reduce, upperFirst } from 'lodash';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { compose, createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { getColorClassName, getColorObjectByColorValue, getColorObjectByAttributeValues, getMostReadableColor as _getMostReadableColor } from './utils';
import useEditorFeature from '../use-editor-feature';
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
  return createHigherOrderComponent(function (WrappedComponent) {
    return function (props) {
      return createElement(WrappedComponent, _extends({}, props, {
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
  return createHigherOrderComponent(function (WrappedComponent) {
    return function (props) {
      var colors = useEditorFeature('color.palette') || DEFAULT_COLORS;
      return createElement(WrappedComponent, _extends({}, props, {
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
  var colorMap = reduce(colorTypes, function (colorObject, colorType) {
    return _objectSpread(_objectSpread({}, colorObject), isString(colorType) ? _defineProperty({}, colorType, kebabCase(colorType)) : colorType);
  }, {});
  return compose([withColorPalette, function (WrappedComponent) {
    return /*#__PURE__*/function (_Component) {
      _inherits(_class, _Component);

      var _super = _createSuper(_class);

      function _class(props) {
        var _this;

        _classCallCheck(this, _class);

        _this = _super.call(this, props);
        _this.setters = _this.createSetters();
        _this.colorUtils = {
          getMostReadableColor: _this.getMostReadableColor.bind(_assertThisInitialized(_this))
        };
        _this.state = {};
        return _this;
      }

      _createClass(_class, [{
        key: "getMostReadableColor",
        value: function getMostReadableColor(colorValue) {
          var colors = this.props.colors;
          return _getMostReadableColor(colors, colorValue);
        }
      }, {
        key: "createSetters",
        value: function createSetters() {
          var _this2 = this;

          return reduce(colorMap, function (settersAccumulator, colorContext, colorAttributeName) {
            var upperFirstColorAttributeName = upperFirst(colorAttributeName);
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

            var colorObject = getColorObjectByColorValue(_this3.props.colors, colorValue);

            _this3.props.setAttributes((_this3$props$setAttri = {}, _defineProperty(_this3$props$setAttri, colorAttributeName, colorObject && colorObject.slug ? colorObject.slug : undefined), _defineProperty(_this3$props$setAttri, customColorAttributeName, colorObject && colorObject.slug ? undefined : colorValue), _this3$props$setAttri));
          };
        }
      }, {
        key: "render",
        value: function render() {
          return createElement(WrappedComponent, _objectSpread(_objectSpread(_objectSpread(_objectSpread({}, this.props), {}, {
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
          return reduce(colorMap, function (newState, colorContext, colorAttributeName) {
            var colorObject = getColorObjectByAttributeValues(colors, attributes[colorAttributeName], attributes["custom".concat(upperFirst(colorAttributeName))]);
            var previousColorObject = previousState[colorAttributeName];
            var previousColor = get(previousColorObject, ['color']);
            /**
             * The "and previousColorObject" condition checks that a previous color object was already computed.
             * At the start previousColorObject and colorValue are both equal to undefined
             * bus as previousColorObject does not exist we should compute the object.
             */

            if (previousColor === colorObject.color && previousColorObject) {
              newState[colorAttributeName] = previousColorObject;
            } else {
              newState[colorAttributeName] = _objectSpread(_objectSpread({}, colorObject), {}, {
                class: getColorClassName(colorContext, colorObject.slug)
              });
            }

            return newState;
          }, {});
        }
      }]);

      return _class;
    }(Component);
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


export function createCustomColorsHOC(colorsArray) {
  return function () {
    var withColorPalette = withCustomColorPalette(colorsArray);

    for (var _len = arguments.length, colorTypes = new Array(_len), _key = 0; _key < _len; _key++) {
      colorTypes[_key] = arguments[_key];
    }

    return createHigherOrderComponent(createColorHOC(colorTypes, withColorPalette), 'withCustomColors');
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

export default function withColors() {
  var withColorPalette = withEditorColorPalette();

  for (var _len2 = arguments.length, colorTypes = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    colorTypes[_key2] = arguments[_key2];
  }

  return createHigherOrderComponent(createColorHOC(colorTypes, withColorPalette), 'withColors');
}
//# sourceMappingURL=with-colors.js.map