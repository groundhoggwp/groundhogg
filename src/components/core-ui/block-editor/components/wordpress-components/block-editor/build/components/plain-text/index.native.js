"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _style = _interopRequireDefault(require("./style.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var PlainText = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(PlainText, _Component);

  var _super = _createSuper(PlainText);

  function PlainText() {
    var _this;

    (0, _classCallCheck2.default)(this, PlainText);
    _this = _super.apply(this, arguments);
    _this.isAndroid = _reactNative.Platform.OS === 'android';
    return _this;
  }

  (0, _createClass2.default)(PlainText, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      // if isSelected is true, we should request the focus on this TextInput
      if (this._input.isFocused() === false && this.props.isSelected) {
        if (this.isAndroid) {
          /*
           * There seems to be an issue in React Native where the keyboard doesn't show if called shortly after rendering.
           * As a common work around this delay is used.
           * https://github.com/facebook/react-native/issues/19366#issuecomment-400603928
           */
          this.timeoutID = setTimeout(function () {
            _this2._input.focus();
          }, 100);
        } else {
          this._input.focus();
        }
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      if (!this.props.isSelected && prevProps.isSelected) {
        this._input.blur();
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      if (this.isAndroid) {
        clearTimeout(this.timeoutID);
      }
    }
  }, {
    key: "focus",
    value: function focus() {
      this._input.focus();
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      return (0, _element.createElement)(_reactNative.TextInput, (0, _extends2.default)({}, this.props, {
        ref: function ref(x) {
          return _this3._input = x;
        },
        onChange: function onChange(event) {
          _this3.props.onChange(event.nativeEvent.text);
        },
        onFocus: this.props.onFocus // always assign onFocus as a props
        ,
        onBlur: this.props.onBlur // always assign onBlur as a props
        ,
        fontFamily: this.props.style && this.props.style.fontFamily || _style.default['block-editor-plain-text'].fontFamily,
        style: this.props.style || _style.default['block-editor-plain-text'],
        scrollEnabled: false
      }));
    }
  }]);
  return PlainText;
}(_element.Component);

exports.default = PlainText;
//# sourceMappingURL=index.native.js.map