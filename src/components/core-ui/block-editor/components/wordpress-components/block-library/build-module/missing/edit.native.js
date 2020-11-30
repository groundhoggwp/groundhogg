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
 * External dependencies
 */
import { View, Text, TouchableWithoutFeedback } from 'react-native';
/**
 * WordPress dependencies
 */

import { requestUnsupportedBlockFallback, sendActionButtonPressedAction, actionButtons } from '@wordpress/react-native-bridge';
import { BottomSheet, Icon, withUIStrings } from '@wordpress/components';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { coreBlocks } from '@wordpress/block-library';
import { normalizeIconObject } from '@wordpress/blocks';
import { Component } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { help, plugins } from '@wordpress/icons';
import { withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import styles from './style.scss';
export var UnsupportedBlockEdit = /*#__PURE__*/function (_Component) {
  _inherits(UnsupportedBlockEdit, _Component);

  var _super = _createSuper(UnsupportedBlockEdit);

  function UnsupportedBlockEdit(props) {
    var _this;

    _classCallCheck(this, UnsupportedBlockEdit);

    _this = _super.call(this, props);
    _this.state = {
      showHelp: false
    };
    _this.toggleSheet = _this.toggleSheet.bind(_assertThisInitialized(_this));
    _this.requestFallback = _this.requestFallback.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(UnsupportedBlockEdit, [{
    key: "toggleSheet",
    value: function toggleSheet() {
      this.setState({
        showHelp: !this.state.showHelp
      });
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      if (this.timeout) {
        clearTimeout(this.timeout);
      }
    }
  }, {
    key: "renderHelpIcon",
    value: function renderHelpIcon() {
      var infoIconStyle = this.props.getStylesFromColorScheme(styles.infoIcon, styles.infoIconDark);
      return createElement(View, {
        style: styles.helpIconContainer
      }, createElement(Icon, {
        className: "unsupported-icon-help",
        label: __('Help icon'),
        icon: help,
        color: infoIconStyle.color
      }));
    }
  }, {
    key: "requestFallback",
    value: function requestFallback() {
      if (this.props.canEnableUnsupportedBlockEditor && this.props.isUnsupportedBlockEditorSupported === false) {
        this.toggleSheet();
        this.setState({
          sendButtonPressMessage: true
        });
      } else {
        this.toggleSheet();
        this.setState({
          sendFallbackMessage: true
        });
      }
    }
  }, {
    key: "renderSheet",
    value: function renderSheet(blockTitle, blockName) {
      var _this2 = this,
          _this$props$uiStrings,
          _this$props$uiStrings2;

      var _this$props = this.props,
          getStylesFromColorScheme = _this$props.getStylesFromColorScheme,
          attributes = _this$props.attributes,
          clientId = _this$props.clientId,
          isUnsupportedBlockEditorSupported = _this$props.isUnsupportedBlockEditorSupported,
          canEnableUnsupportedBlockEditor = _this$props.canEnableUnsupportedBlockEditor;
      var infoTextStyle = getStylesFromColorScheme(styles.infoText, styles.infoTextDark);
      var infoTitleStyle = getStylesFromColorScheme(styles.infoTitle, styles.infoTitleDark);
      var infoDescriptionStyle = getStylesFromColorScheme(styles.infoDescription, styles.infoDescriptionDark);
      var infoSheetIconStyle = getStylesFromColorScheme(styles.infoSheetIcon, styles.infoSheetIconDark);
      /* translators: Missing block alert title. %s: The localized block name */

      var titleFormat = __("'%s' is not fully-supported");

      var infoTitle = sprintf(titleFormat, blockTitle);
      var actionButtonStyle = getStylesFromColorScheme(styles.actionButton, styles.actionButtonDark);
      return createElement(BottomSheet, {
        isVisible: this.state.showHelp,
        hideHeader: true,
        onClose: this.toggleSheet,
        onModalHide: function onModalHide() {
          if (_this2.state.sendFallbackMessage) {
            // On iOS, onModalHide is called when the controller is still part of the hierarchy.
            // A small delay will ensure that the controller has already been removed.
            _this2.timeout = setTimeout(function () {
              // for the Classic block, the content is kept in the `content` attribute
              var content = blockName === 'core/freeform' ? attributes.content : attributes.originalContent;
              requestUnsupportedBlockFallback(content, clientId, blockName, blockTitle);
            }, 100);

            _this2.setState({
              sendFallbackMessage: false
            });
          } else if (_this2.state.sendButtonPressMessage) {
            _this2.timeout = setTimeout(function () {
              sendActionButtonPressedAction(actionButtons.missingBlockAlertActionButton);
            }, 100);

            _this2.setState({
              sendButtonPressMessage: false
            });
          }
        }
      }, createElement(View, {
        style: styles.infoContainer
      }, createElement(Icon, {
        icon: help,
        color: infoSheetIconStyle.color,
        size: styles.infoSheetIcon.size
      }), createElement(Text, {
        style: [infoTextStyle, infoTitleStyle]
      }, infoTitle), createElement(Text, {
        style: [infoTextStyle, infoDescriptionStyle]
      }, (_this$props$uiStrings = this.props.uiStrings['missing-block-detail']) !== null && _this$props$uiStrings !== void 0 ? _this$props$uiStrings : __('We are working hard to add more blocks with each release.'))), (isUnsupportedBlockEditorSupported || canEnableUnsupportedBlockEditor) && createElement(Fragment, null, createElement(BottomSheet.Cell, {
        label: (_this$props$uiStrings2 = this.props.uiStrings['missing-block-action-button']) !== null && _this$props$uiStrings2 !== void 0 ? _this$props$uiStrings2 : __('Edit using web editor'),
        separatorType: "topFullWidth",
        onPress: this.requestFallback,
        labelStyle: actionButtonStyle
      }), createElement(BottomSheet.Cell, {
        label: __('Dismiss'),
        separatorType: "topFullWidth",
        onPress: this.toggleSheet,
        labelStyle: actionButtonStyle
      })));
    }
  }, {
    key: "render",
    value: function render() {
      var originalName = this.props.attributes.originalName;
      var _this$props2 = this.props,
          getStylesFromColorScheme = _this$props2.getStylesFromColorScheme,
          preferredColorScheme = _this$props2.preferredColorScheme;
      var blockType = coreBlocks[originalName];
      var title = blockType ? blockType.settings.title : originalName;
      var titleStyle = getStylesFromColorScheme(styles.unsupportedBlockMessage, styles.unsupportedBlockMessageDark);
      var subTitleStyle = getStylesFromColorScheme(styles.unsupportedBlockSubtitle, styles.unsupportedBlockSubtitleDark);
      var subtitle = createElement(Text, {
        style: subTitleStyle
      }, __('Unsupported'));
      var icon = blockType ? normalizeIconObject(blockType.settings.icon) : plugins;
      var iconStyle = getStylesFromColorScheme(styles.unsupportedBlockIcon, styles.unsupportedBlockIconDark);
      var iconClassName = 'unsupported-icon' + '-' + preferredColorScheme;
      return createElement(TouchableWithoutFeedback, {
        disabled: !this.props.isSelected,
        accessibilityLabel: __('Help button'),
        accessibilityRole: 'button',
        accessibilityHint: __('Tap here to show help'),
        onPress: this.toggleSheet
      }, createElement(View, {
        style: getStylesFromColorScheme(styles.unsupportedBlock, styles.unsupportedBlockDark)
      }, this.renderHelpIcon(), createElement(Icon, {
        className: iconClassName,
        icon: icon && icon.src ? icon.src : icon,
        color: iconStyle.color
      }), createElement(Text, {
        style: titleStyle
      }, title), subtitle, this.renderSheet(title, originalName)));
    }
  }]);

  return UnsupportedBlockEdit;
}(Component);
export default compose([withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  return {
    isUnsupportedBlockEditorSupported: getSettings('capabilities').unsupportedBlockEditor === true,
    canEnableUnsupportedBlockEditor: getSettings('capabilities').canEnableUnsupportedBlockEditor === true
  };
}), withPreferredColorScheme, withUIStrings])(UnsupportedBlockEdit);
//# sourceMappingURL=edit.native.js.map