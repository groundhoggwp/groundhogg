"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var HTMLEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(HTMLEdit, _Component);

  var _super = _createSuper(HTMLEdit);

  function HTMLEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, HTMLEdit);
    _this = _super.apply(this, arguments);
    _this.state = {
      isPreview: false,
      styles: []
    };
    _this.switchToHTML = _this.switchToHTML.bind((0, _assertThisInitialized2.default)(_this));
    _this.switchToPreview = _this.switchToPreview.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(HTMLEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var styles = this.props.styles; // Default styles used to unset some of the styles
      // that might be inherited from the editor style.

      var defaultStyles = "\n\t\t\thtml,body,:root {\n\t\t\t\tmargin: 0 !important;\n\t\t\t\tpadding: 0 !important;\n\t\t\t\toverflow: visible !important;\n\t\t\t\tmin-height: auto !important;\n\t\t\t}\n\t\t";
      this.setState({
        styles: [defaultStyles].concat((0, _toConsumableArray2.default)((0, _blockEditor.transformStyles)(styles)))
      });
    }
  }, {
    key: "switchToPreview",
    value: function switchToPreview() {
      this.setState({
        isPreview: true
      });
    }
  }, {
    key: "switchToHTML",
    value: function switchToHTML() {
      this.setState({
        isPreview: false
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          attributes = _this$props.attributes,
          setAttributes = _this$props.setAttributes;
      var _this$state = this.state,
          isPreview = _this$state.isPreview,
          styles = _this$state.styles;
      return (0, _element.createElement)("div", {
        className: "wp-block-html"
      }, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
        className: "components-tab-button",
        isPressed: !isPreview,
        onClick: this.switchToHTML
      }, (0, _element.createElement)("span", null, "HTML")), (0, _element.createElement)(_components.ToolbarButton, {
        className: "components-tab-button",
        isPressed: isPreview,
        onClick: this.switchToPreview
      }, (0, _element.createElement)("span", null, (0, _i18n.__)('Preview'))))), (0, _element.createElement)(_components.Disabled.Consumer, null, function (isDisabled) {
        return isPreview || isDisabled ? (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.SandBox, {
          html: attributes.content,
          styles: styles
        }), !_this2.props.isSelected && (0, _element.createElement)("div", {
          className: "block-library-html__preview-overlay"
        })) : (0, _element.createElement)(_blockEditor.PlainText, {
          value: attributes.content,
          onChange: function onChange(content) {
            return setAttributes({
              content: content
            });
          },
          placeholder: (0, _i18n.__)('Write HTML…'),
          "aria-label": (0, _i18n.__)('HTML')
        });
      }));
    }
  }]);
  return HTMLEdit;
}(_element.Component);

var _default = (0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  return {
    styles: getSettings().styles
  };
})(HTMLEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map