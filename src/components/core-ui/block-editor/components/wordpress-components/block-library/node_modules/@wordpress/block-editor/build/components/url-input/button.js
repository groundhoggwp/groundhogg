"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _ = _interopRequireDefault(require("./"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var URLInputButton = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(URLInputButton, _Component);

  var _super = _createSuper(URLInputButton);

  function URLInputButton() {
    var _this;

    (0, _classCallCheck2.default)(this, URLInputButton);
    _this = _super.apply(this, arguments);
    _this.toggle = _this.toggle.bind((0, _assertThisInitialized2.default)(_this));
    _this.submitLink = _this.submitLink.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      expanded: false
    };
    return _this;
  }

  (0, _createClass2.default)(URLInputButton, [{
    key: "toggle",
    value: function toggle() {
      this.setState({
        expanded: !this.state.expanded
      });
    }
  }, {
    key: "submitLink",
    value: function submitLink(event) {
      event.preventDefault();
      this.toggle();
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          url = _this$props.url,
          onChange = _this$props.onChange;
      var expanded = this.state.expanded;
      var buttonLabel = url ? (0, _i18n.__)('Edit link') : (0, _i18n.__)('Insert link');
      return (0, _element.createElement)("div", {
        className: "block-editor-url-input__button"
      }, (0, _element.createElement)(_components.Button, {
        icon: _icons.link,
        label: buttonLabel,
        onClick: this.toggle,
        className: "components-toolbar__control",
        isPressed: !!url
      }), expanded && (0, _element.createElement)("form", {
        className: "block-editor-url-input__button-modal",
        onSubmit: this.submitLink
      }, (0, _element.createElement)("div", {
        className: "block-editor-url-input__button-modal-line"
      }, (0, _element.createElement)(_components.Button, {
        className: "block-editor-url-input__back",
        icon: _icons.arrowLeft,
        label: (0, _i18n.__)('Close'),
        onClick: this.toggle
      }), (0, _element.createElement)(_.default, {
        value: url || '',
        onChange: onChange
      }), (0, _element.createElement)(_components.Button, {
        icon: _icons.keyboardReturn,
        label: (0, _i18n.__)('Submit'),
        type: "submit"
      }))));
    }
  }]);
  return URLInputButton;
}(_element.Component);
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/url-input/README.md
 */


var _default = URLInputButton;
exports.default = _default;
//# sourceMappingURL=button.js.map