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
import { View } from 'react-native';
import Hr from 'react-native-hr';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './editor.scss';
export var MoreEdit = /*#__PURE__*/function (_Component) {
  _inherits(MoreEdit, _Component);

  var _super = _createSuper(MoreEdit);

  function MoreEdit() {
    var _this;

    _classCallCheck(this, MoreEdit);

    _this = _super.apply(this, arguments);
    _this.state = {
      defaultText: __('Read more')
    };
    return _this;
  }

  _createClass(MoreEdit, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          getStylesFromColorScheme = _this$props.getStylesFromColorScheme;
      var customText = attributes.customText;
      var defaultText = this.state.defaultText;
      var content = customText || defaultText;
      var textStyle = getStylesFromColorScheme(styles.moreText, styles.moreTextDark);
      var lineStyle = getStylesFromColorScheme(styles.moreLine, styles.moreLineDark);
      return createElement(View, null, createElement(Hr, {
        text: content,
        marginLeft: 0,
        marginRight: 0,
        textStyle: textStyle,
        lineStyle: lineStyle
      }));
    }
  }]);

  return MoreEdit;
}(Component);
export default withPreferredColorScheme(MoreEdit);
//# sourceMappingURL=edit.native.js.map