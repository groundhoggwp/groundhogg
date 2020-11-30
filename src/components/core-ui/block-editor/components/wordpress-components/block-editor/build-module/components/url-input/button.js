import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { link, keyboardReturn, arrowLeft } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import URLInput from './';

var URLInputButton = /*#__PURE__*/function (_Component) {
  _inherits(URLInputButton, _Component);

  var _super = _createSuper(URLInputButton);

  function URLInputButton() {
    var _this;

    _classCallCheck(this, URLInputButton);

    _this = _super.apply(this, arguments);
    _this.toggle = _this.toggle.bind(_assertThisInitialized(_this));
    _this.submitLink = _this.submitLink.bind(_assertThisInitialized(_this));
    _this.state = {
      expanded: false
    };
    return _this;
  }

  _createClass(URLInputButton, [{
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
      var buttonLabel = url ? __('Edit link') : __('Insert link');
      return createElement("div", {
        className: "block-editor-url-input__button"
      }, createElement(Button, {
        icon: link,
        label: buttonLabel,
        onClick: this.toggle,
        className: "components-toolbar__control",
        isPressed: !!url
      }), expanded && createElement("form", {
        className: "block-editor-url-input__button-modal",
        onSubmit: this.submitLink
      }, createElement("div", {
        className: "block-editor-url-input__button-modal-line"
      }, createElement(Button, {
        className: "block-editor-url-input__back",
        icon: arrowLeft,
        label: __('Close'),
        onClick: this.toggle
      }), createElement(URLInput, {
        value: url || '',
        onChange: onChange
      }), createElement(Button, {
        icon: keyboardReturn,
        label: __('Submit'),
        type: "submit"
      }))));
    }
  }]);

  return URLInputButton;
}(Component);
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/url-input/README.md
 */


export default URLInputButton;
//# sourceMappingURL=button.js.map