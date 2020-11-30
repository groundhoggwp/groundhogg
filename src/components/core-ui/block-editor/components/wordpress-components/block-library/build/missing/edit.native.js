"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.UnsupportedBlockEdit = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockLibrary = require("@wordpress/block-library");

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _data = require("@wordpress/data");

var _style = _interopRequireDefault(require("./style.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var UnsupportedBlockEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(UnsupportedBlockEdit, _Component);

  var _super = _createSuper(UnsupportedBlockEdit);

  function UnsupportedBlockEdit(props) {
    var _this;

    (0, _classCallCheck2.default)(this, UnsupportedBlockEdit);
    _this = _super.call(this, props);
    _this.state = {
      showHelp: false
    };
    _this.toggleSheet = _this.toggleSheet.bind((0, _assertThisInitialized2.default)(_this));
    _this.requestFallback = _this.requestFallback.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(UnsupportedBlockEdit, [{
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
      var infoIconStyle = this.props.getStylesFromColorScheme(_style.default.infoIcon, _style.default.infoIconDark);
      return (0, _element.createElement)(_reactNative.View, {
        style: _style.default.helpIconContainer
      }, (0, _element.createElement)(_components.Icon, {
        className: "unsupported-icon-help",
        label: (0, _i18n.__)('Help icon'),
        icon: _icons.help,
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
      var infoTextStyle = getStylesFromColorScheme(_style.default.infoText, _style.default.infoTextDark);
      var infoTitleStyle = getStylesFromColorScheme(_style.default.infoTitle, _style.default.infoTitleDark);
      var infoDescriptionStyle = getStylesFromColorScheme(_style.default.infoDescription, _style.default.infoDescriptionDark);
      var infoSheetIconStyle = getStylesFromColorScheme(_style.default.infoSheetIcon, _style.default.infoSheetIconDark);
      /* translators: Missing block alert title. %s: The localized block name */

      var titleFormat = (0, _i18n.__)("'%s' is not fully-supported");
      var infoTitle = (0, _i18n.sprintf)(titleFormat, blockTitle);
      var actionButtonStyle = getStylesFromColorScheme(_style.default.actionButton, _style.default.actionButtonDark);
      return (0, _element.createElement)(_components.BottomSheet, {
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
              (0, _reactNativeBridge.requestUnsupportedBlockFallback)(content, clientId, blockName, blockTitle);
            }, 100);

            _this2.setState({
              sendFallbackMessage: false
            });
          } else if (_this2.state.sendButtonPressMessage) {
            _this2.timeout = setTimeout(function () {
              (0, _reactNativeBridge.sendActionButtonPressedAction)(_reactNativeBridge.actionButtons.missingBlockAlertActionButton);
            }, 100);

            _this2.setState({
              sendButtonPressMessage: false
            });
          }
        }
      }, (0, _element.createElement)(_reactNative.View, {
        style: _style.default.infoContainer
      }, (0, _element.createElement)(_components.Icon, {
        icon: _icons.help,
        color: infoSheetIconStyle.color,
        size: _style.default.infoSheetIcon.size
      }), (0, _element.createElement)(_reactNative.Text, {
        style: [infoTextStyle, infoTitleStyle]
      }, infoTitle), (0, _element.createElement)(_reactNative.Text, {
        style: [infoTextStyle, infoDescriptionStyle]
      }, (_this$props$uiStrings = this.props.uiStrings['missing-block-detail']) !== null && _this$props$uiStrings !== void 0 ? _this$props$uiStrings : (0, _i18n.__)('We are working hard to add more blocks with each release.'))), (isUnsupportedBlockEditorSupported || canEnableUnsupportedBlockEditor) && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.BottomSheet.Cell, {
        label: (_this$props$uiStrings2 = this.props.uiStrings['missing-block-action-button']) !== null && _this$props$uiStrings2 !== void 0 ? _this$props$uiStrings2 : (0, _i18n.__)('Edit using web editor'),
        separatorType: "topFullWidth",
        onPress: this.requestFallback,
        labelStyle: actionButtonStyle
      }), (0, _element.createElement)(_components.BottomSheet.Cell, {
        label: (0, _i18n.__)('Dismiss'),
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
      var blockType = _blockLibrary.coreBlocks[originalName];
      var title = blockType ? blockType.settings.title : originalName;
      var titleStyle = getStylesFromColorScheme(_style.default.unsupportedBlockMessage, _style.default.unsupportedBlockMessageDark);
      var subTitleStyle = getStylesFromColorScheme(_style.default.unsupportedBlockSubtitle, _style.default.unsupportedBlockSubtitleDark);
      var subtitle = (0, _element.createElement)(_reactNative.Text, {
        style: subTitleStyle
      }, (0, _i18n.__)('Unsupported'));
      var icon = blockType ? (0, _blocks.normalizeIconObject)(blockType.settings.icon) : _icons.plugins;
      var iconStyle = getStylesFromColorScheme(_style.default.unsupportedBlockIcon, _style.default.unsupportedBlockIconDark);
      var iconClassName = 'unsupported-icon' + '-' + preferredColorScheme;
      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        disabled: !this.props.isSelected,
        accessibilityLabel: (0, _i18n.__)('Help button'),
        accessibilityRole: 'button',
        accessibilityHint: (0, _i18n.__)('Tap here to show help'),
        onPress: this.toggleSheet
      }, (0, _element.createElement)(_reactNative.View, {
        style: getStylesFromColorScheme(_style.default.unsupportedBlock, _style.default.unsupportedBlockDark)
      }, this.renderHelpIcon(), (0, _element.createElement)(_components.Icon, {
        className: iconClassName,
        icon: icon && icon.src ? icon.src : icon,
        color: iconStyle.color
      }), (0, _element.createElement)(_reactNative.Text, {
        style: titleStyle
      }, title), subtitle, this.renderSheet(title, originalName)));
    }
  }]);
  return UnsupportedBlockEdit;
}(_element.Component);

exports.UnsupportedBlockEdit = UnsupportedBlockEdit;

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      getSettings = _select.getSettings;

  return {
    isUnsupportedBlockEditorSupported: getSettings('capabilities').unsupportedBlockEditor === true,
    canEnableUnsupportedBlockEditor: getSettings('capabilities').canEnableUnsupportedBlockEditor === true
  };
}), _compose.withPreferredColorScheme, _components.withUIStrings])(UnsupportedBlockEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map