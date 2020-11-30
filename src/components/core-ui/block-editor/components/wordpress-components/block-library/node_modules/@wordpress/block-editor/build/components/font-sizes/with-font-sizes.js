"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _utils = require("./utils");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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

var _default = function _default() {
  for (var _len = arguments.length, fontSizeNames = new Array(_len), _key = 0; _key < _len; _key++) {
    fontSizeNames[_key] = arguments[_key];
  }

  /*
   * Computes an object whose key is the font size attribute name as passed in the array,
   * and the value is the custom font size attribute name.
   * Custom font size is automatically compted by appending custom followed by the font size attribute name in with the first letter capitalized.
   */
  var fontSizeAttributeNames = (0, _lodash.reduce)(fontSizeNames, function (fontSizeAttributeNamesAccumulator, fontSizeAttributeName) {
    fontSizeAttributeNamesAccumulator[fontSizeAttributeName] = "custom".concat((0, _lodash.upperFirst)(fontSizeAttributeName));
    return fontSizeAttributeNamesAccumulator;
  }, {});
  return (0, _compose.createHigherOrderComponent)((0, _compose.compose)([(0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
    return function (props) {
      var fontSizes = (0, _useEditorFeature.default)('typography.fontSizes') || DEFAULT_FONT_SIZES;
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
        fontSizes: fontSizes
      }));
    };
  }, 'withFontSizes'), function (WrappedComponent) {
    return /*#__PURE__*/function (_Component) {
      (0, _inherits2.default)(_class, _Component);

      var _super = _createSuper(_class);

      function _class(props) {
        var _this;

        (0, _classCallCheck2.default)(this, _class);
        _this = _super.call(this, props);
        _this.setters = _this.createSetters();
        _this.state = {};
        return _this;
      }

      (0, _createClass2.default)(_class, [{
        key: "createSetters",
        value: function createSetters() {
          var _this2 = this;

          return (0, _lodash.reduce)(fontSizeAttributeNames, function (settersAccumulator, customFontSizeAttributeName, fontSizeAttributeName) {
            var upperFirstFontSizeAttributeName = (0, _lodash.upperFirst)(fontSizeAttributeName);
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

            var fontSizeObject = (0, _lodash.find)(_this3.props.fontSizes, {
              size: Number(fontSizeValue)
            });

            _this3.props.setAttributes((_this3$props$setAttri = {}, (0, _defineProperty2.default)(_this3$props$setAttri, fontSizeAttributeName, fontSizeObject && fontSizeObject.slug ? fontSizeObject.slug : undefined), (0, _defineProperty2.default)(_this3$props$setAttri, customFontSizeAttributeName, fontSizeObject && fontSizeObject.slug ? undefined : fontSizeValue), _this3$props$setAttri));
          };
        }
      }, {
        key: "render",
        value: function render() {
          return (0, _element.createElement)(WrappedComponent, _objectSpread(_objectSpread(_objectSpread({}, this.props), {}, {
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

          if (!(0, _lodash.some)(fontSizeAttributeNames, didAttributesChange)) {
            return null;
          }

          var newState = (0, _lodash.reduce)((0, _lodash.pickBy)(fontSizeAttributeNames, didAttributesChange), function (newStateAccumulator, customFontSizeAttributeName, fontSizeAttributeName) {
            var fontSizeAttributeValue = attributes[fontSizeAttributeName];
            var fontSizeObject = (0, _utils.getFontSize)(fontSizes, fontSizeAttributeValue, attributes[customFontSizeAttributeName]);
            newStateAccumulator[fontSizeAttributeName] = _objectSpread(_objectSpread({}, fontSizeObject), {}, {
              class: (0, _utils.getFontSizeClass)(fontSizeAttributeValue)
            });
            return newStateAccumulator;
          }, {});
          return _objectSpread(_objectSpread({}, previousState), newState);
        }
      }]);
      return _class;
    }(_element.Component);
  }]), 'withFontSizes');
};

exports.default = _default;
//# sourceMappingURL=with-font-sizes.js.map