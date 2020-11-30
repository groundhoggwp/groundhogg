"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.MoreEdit = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _reactNativeHr = _interopRequireDefault(require("react-native-hr"));

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _editor = _interopRequireDefault(require("./editor.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MoreEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(MoreEdit, _Component);

  var _super = _createSuper(MoreEdit);

  function MoreEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, MoreEdit);
    _this = _super.apply(this, arguments);
    _this.state = {
      defaultText: (0, _i18n.__)('Read more')
    };
    return _this;
  }

  (0, _createClass2.default)(MoreEdit, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          getStylesFromColorScheme = _this$props.getStylesFromColorScheme;
      var customText = attributes.customText;
      var defaultText = this.state.defaultText;
      var content = customText || defaultText;
      var textStyle = getStylesFromColorScheme(_editor.default.moreText, _editor.default.moreTextDark);
      var lineStyle = getStylesFromColorScheme(_editor.default.moreLine, _editor.default.moreLineDark);
      return (0, _element.createElement)(_reactNative.View, null, (0, _element.createElement)(_reactNativeHr.default, {
        text: content,
        marginLeft: 0,
        marginRight: 0,
        textStyle: textStyle,
        lineStyle: lineStyle
      }));
    }
  }]);
  return MoreEdit;
}(_element.Component);

exports.MoreEdit = MoreEdit;

var _default = (0, _compose.withPreferredColorScheme)(MoreEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map