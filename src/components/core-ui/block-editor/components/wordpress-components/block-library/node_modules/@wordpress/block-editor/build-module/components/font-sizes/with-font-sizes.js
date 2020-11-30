import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { find, pickBy, reduce, some, upperFirst } from 'lodash';
/**
 * WordPress dependencies
 */

import { createHigherOrderComponent, compose } from '@wordpress/compose';
import { Component } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { getFontSize, getFontSizeClass } from './utils';
import useEditorFeature from '../use-editor-feature';
var DEFAULT_FONT_SIZES = [];
/**
 * Higher-order component, which handles font size logic for class generation,
 * font size value retrieval, and font size change handling.
 *
 * @param {...(Object|string)} fontSizeNames The arguments should all be strings.
 *                                           Each string contains the font size
 *                                           attribute name e.g: 'fontSize'.
 *
 * @return {Function} Higher-order component.
 */

export default (function () {
  for (var _len = arguments.length, fontSizeNames = new Array(_len), _key = 0; _key < _len; _key++) {
    fontSizeNames[_key] = arguments[_key];
  }

  /*
   * Computes an object whose key is the font size attribute name as passed in the array,
   * and the value is the custom font size attribute name.
   * Custom font size is automatically compted by appending custom followed by the font size attribute name in with the first letter capitalized.
   */
  var fontSizeAttributeNames = reduce(fontSizeNames, function (fontSizeAttributeNamesAccumulator, fontSizeAttributeName) {
    fontSizeAttributeNamesAccumulator[fontSizeAttributeName] = "custom".concat(upperFirst(fontSizeAttributeName));
    return fontSizeAttributeNamesAccumulator;
  }, {});
  return createHigherOrderComponent(compose([createHigherOrderComponent(function (WrappedComponent) {
    return function (props) {
      var fontSizes = useEditorFeature('typography.fontSizes') || DEFAULT_FONT_SIZES;
      return createElement(WrappedComponent, _extends({}, props, {
        fontSizes: fontSizes
      }));
    };
  }, 'withFontSizes'), function (WrappedComponent) {
    return /*#__PURE__*/function (_Component) {
      _inherits(_class, _Component);

      var _super = _createSuper(_class);

      function _class(props) {
        var _this;

        _classCallCheck(this, _class);

        _this = _super.call(this, props);
        _this.setters = _this.createSetters();
        _this.state = {};
        return _this;
      }

      _createClass(_class, [{
        key: "createSetters",
        value: function createSetters() {
          var _this2 = this;

          return reduce(fontSizeAttributeNames, function (settersAccumulator, customFontSizeAttributeName, fontSizeAttributeName) {
            var upperFirstFontSizeAttributeName = upperFirst(fontSizeAttributeName);
            settersAccumulator["set".concat(upperFirstFontSizeAttributeName)] = _this2.createSetFontSize(fontSizeAttributeName, customFontSizeAttributeName);
            return settersAccumulator;
          }, {});
        }
      }, {
        key: "createSetFontSize",
        value: function createSetFontSize(fontSizeAttributeName, customFontSizeAttributeName) {
          var _this3 = this;

          return function (fontSizeValue) {
            var _this3$props$setAttri;

            var fontSizeObject = find(_this3.props.fontSizes, {
              size: Number(fontSizeValue)
            });

            _this3.props.setAttributes((_this3$props$setAttri = {}, _defineProperty(_this3$props$setAttri, fontSizeAttributeName, fontSizeObject && fontSizeObject.slug ? fontSizeObject.slug : undefined), _defineProperty(_this3$props$setAttri, customFontSizeAttributeName, fontSizeObject && fontSizeObject.slug ? undefined : fontSizeValue), _this3$props$setAttri));
          };
        }
      }, {
        key: "render",
        value: function render() {
          return createElement(WrappedComponent, _objectSpread(_objectSpread(_objectSpread({}, this.props), {}, {
            fontSizes: undefined
          }, this.state), this.setters));
        }
      }], [{
        key: "getDerivedStateFromProps",
        value: function getDerivedStateFromProps(_ref, previousState) {
          var attributes = _ref.attributes,
              fontSizes = _ref.fontSizes;

          var didAttributesChange = function didAttributesChange(customFontSizeAttributeName, fontSizeAttributeName) {
            if (previousState[fontSizeAttributeName]) {
              // if new font size is name compare with the previous slug
              if (attributes[fontSizeAttributeName]) {
                return attributes[fontSizeAttributeName] !== previousState[fontSizeAttributeName].slug;
              } // if font size is not named, update when the font size value changes.


              return previousState[fontSizeAttributeName].size !== attributes[customFontSizeAttributeName];
            } // in this case we need to build the font size object


            return true;
          };

          if (!some(fontSizeAttributeNames, didAttributesChange)) {
            return null;
          }

          var newState = reduce(pickBy(fontSizeAttributeNames, didAttributesChange), function (newStateAccumulator, customFontSizeAttributeName, fontSizeAttributeName) {
            var fontSizeAttributeValue = attributes[fontSizeAttributeName];
            var fontSizeObject = getFontSize(fontSizes, fontSizeAttributeValue, attributes[customFontSizeAttributeName]);
            newStateAccumulator[fontSizeAttributeName] = _objectSpread(_objectSpread({}, fontSizeObject), {}, {
              class: getFontSizeClass(fontSizeAttributeValue)
            });
            return newStateAccumulator;
          }, {});
          return _objectSpread(_objectSpread({}, previousState), newState);
        }
      }]);

      return _class;
    }(Component);
  }]), 'withFontSizes');
});
//# sourceMappingURL=with-font-sizes.js.map