"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.__unstableRichTextInputEvent = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _element = require("@wordpress/element");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var __unstableRichTextInputEvent = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(__unstableRichTextInputEvent, _Component);

  var _super = _createSuper(__unstableRichTextInputEvent);

  function __unstableRichTextInputEvent() {
    var _this;

    (0, _classCallCheck2.default)(this, __unstableRichTextInputEvent);
    _this = _super.apply(this, arguments);
    _this.onInput = _this.onInput.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(__unstableRichTextInputEvent, [{
    key: "onInput",
    value: function onInput(event) {
      if (event.inputType === this.props.inputType) {
        this.props.onInput();
      }
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      document.addEventListener('input', this.onInput, true);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      document.removeEventListener('input', this.onInput, true);
    }
  }, {
    key: "render",
    value: function render() {
      return null;
    }
  }]);
  return __unstableRichTextInputEvent;
}(_element.Component);

exports.__unstableRichTextInputEvent = __unstableRichTextInputEvent;
//# sourceMappingURL=input-event.js.map