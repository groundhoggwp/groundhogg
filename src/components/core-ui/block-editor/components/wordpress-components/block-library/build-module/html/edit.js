import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { BlockControls, PlainText, transformStyles } from '@wordpress/block-editor';
import { ToolbarButton, Disabled, SandBox, ToolbarGroup } from '@wordpress/components';
import { withSelect } from '@wordpress/data';

var HTMLEdit = /*#__PURE__*/function (_Component) {
  _inherits(HTMLEdit, _Component);

  var _super = _createSuper(HTMLEdit);

  function HTMLEdit() {
    var _this;

    _classCallCheck(this, HTMLEdit);

    _this = _super.apply(this, arguments);
    _this.state = {
      isPreview: false,
      styles: []
    };
    _this.switchToHTML = _this.switchToHTML.bind(_assertThisInitialized(_this));
    _this.switchToPreview = _this.switchToPreview.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(HTMLEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var styles = this.props.styles; // Default styles used to unset some of the styles
      // that might be inherited from the editor style.

      var defaultStyles = "\n\t\t\thtml,body,:root {\n\t\t\t\tmargin: 0 !important;\n\t\t\t\tpadding: 0 !important;\n\t\t\t\toverflow: visible !important;\n\t\t\t\tmin-height: auto !important;\n\t\t\t}\n\t\t";
      this.setState({
        styles: [defaultStyles].concat(_toConsumableArray(transformStyles(styles)))
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
      return createElement("div", {
        className: "wp-block-html"
      }, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
        className: "components-tab-button",
        isPressed: !isPreview,
        onClick: this.switchToHTML
      }, createElement("span", null, "HTML")), createElement(ToolbarButton, {
        className: "components-tab-button",
        isPressed: isPreview,
        onClick: this.switchToPreview
      }, createElement("span", null, __('Preview'))))), createElement(Disabled.Consumer, null, function (isDisabled) {
        return isPreview || isDisabled ? createElement(Fragment, null, createElement(SandBox, {
          html: attributes.content,
          styles: styles
        }), !_this2.props.isSelected && createElement("div", {
          className: "block-library-html__preview-overlay"
        })) : createElement(PlainText, {
          value: attributes.content,
          onChange: function onChange(content) {
            return setAttributes({
              content: content
            });
          },
          placeholder: __('Write HTML…'),
          "aria-label": __('HTML')
        });
      }));
    }
  }]);

  return HTMLEdit;
}(Component);

export default withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  return {
    styles: getSettings().styles
  };
})(HTMLEdit);
//# sourceMappingURL=edit.js.map