import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { TextInput, Platform } from 'react-native';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './style.scss';

var PlainText = /*#__PURE__*/function (_Component) {
  _inherits(PlainText, _Component);

  var _super = _createSuper(PlainText);

  function PlainText() {
    var _this;

    _classCallCheck(this, PlainText);

    _this = _super.apply(this, arguments);
    _this.isAndroid = Platform.OS === 'android';
    return _this;
  }

  _createClass(PlainText, [{
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

      return createElement(TextInput, _extends({}, this.props, {
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
        fontFamily: this.props.style && this.props.style.fontFamily || styles['block-editor-plain-text'].fontFamily,
        style: this.props.style || styles['block-editor-plain-text'],
        scrollEnabled: false
      }));
    }
  }]);

  return PlainText;
}(Component);

export { PlainText as default };
//# sourceMappingURL=index.native.js.map