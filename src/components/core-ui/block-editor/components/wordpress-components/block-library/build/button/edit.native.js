"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _richText = _interopRequireDefault(require("./rich-text.scss"));

var _editor = _interopRequireDefault(require("./editor.scss"));

var _colorBackground = _interopRequireDefault(require("./color-background"));

var _colorEdit = _interopRequireDefault(require("./color-edit"));

var _colorProps = _interopRequireDefault(require("./color-props"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MIN_BORDER_RADIUS_VALUE = 0;
var MAX_BORDER_RADIUS_VALUE = 50;
var INITIAL_MAX_WIDTH = 108;

var ButtonEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(ButtonEdit, _Component);

  var _super = _createSuper(ButtonEdit);

  function ButtonEdit(props) {
    var _this;

    (0, _classCallCheck2.default)(this, ButtonEdit);
    _this = _super.call(this, props);
    _this.onChangeText = _this.onChangeText.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChangeBorderRadius = _this.onChangeBorderRadius.bind((0, _assertThisInitialized2.default)(_this));
    _this.onClearSettings = _this.onClearSettings.bind((0, _assertThisInitialized2.default)(_this));
    _this.onLayout = _this.onLayout.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetMaxWidth = _this.onSetMaxWidth.bind((0, _assertThisInitialized2.default)(_this));
    _this.dismissSheet = _this.dismissSheet.bind((0, _assertThisInitialized2.default)(_this));
    _this.onShowLinkSettings = _this.onShowLinkSettings.bind((0, _assertThisInitialized2.default)(_this));
    _this.onHideLinkSettings = _this.onHideLinkSettings.bind((0, _assertThisInitialized2.default)(_this));
    _this.onToggleButtonFocus = _this.onToggleButtonFocus.bind((0, _assertThisInitialized2.default)(_this));
    _this.setRef = _this.setRef.bind((0, _assertThisInitialized2.default)(_this));
    _this.onRemove = _this.onRemove.bind((0, _assertThisInitialized2.default)(_this));
    _this.getPlaceholderWidth = _this.getPlaceholderWidth.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      maxWidth: INITIAL_MAX_WIDTH,
      isLinkSheetVisible: false,
      isButtonFocused: true,
      placeholderTextWidth: 0
    };
    return _this;
  }

  (0, _createClass2.default)(ButtonEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.onSetMaxWidth();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      var _this2 = this;

      var _this$props = this.props,
          selectedId = _this$props.selectedId,
          editorSidebarOpened = _this$props.editorSidebarOpened,
          parentWidth = _this$props.parentWidth;
      var _this$state = this.state,
          isLinkSheetVisible = _this$state.isLinkSheetVisible,
          isButtonFocused = _this$state.isButtonFocused;

      if (prevProps.selectedId !== selectedId) {
        this.onToggleButtonFocus(true);
      }

      if (prevProps.parentWidth !== parentWidth) {
        this.onSetMaxWidth();
      } // Blur `RichText` on Android when link settings sheet or button settings sheet is opened,
      // to avoid flashing caret after closing one of them


      if (!prevProps.editorSidebarOpened && editorSidebarOpened || !prevState.isLinkSheetVisible && isLinkSheetVisible) {
        if (_reactNative.Platform.OS === 'android' && this.richTextRef) {
          this.richTextRef.blur();
          this.onToggleButtonFocus(false);
        }
      }

      if (this.richTextRef) {
        var selectedRichText = this.richTextRef.props.id === selectedId;

        if (!selectedRichText && isButtonFocused) {
          this.onToggleButtonFocus(false);
        }

        if (selectedRichText && selectedId !== prevProps.selectedId && !isButtonFocused) {
          _reactNative.AccessibilityInfo.isScreenReaderEnabled().then(function (enabled) {
            if (enabled) {
              _this2.onToggleButtonFocus(true);

              _this2.richTextRef.focus();
            }
          });
        }
      }
    }
  }, {
    key: "getBackgroundColor",
    value: function getBackgroundColor() {
      var _colorAndStyleProps$s, _colorAndStyleProps$s2;

      var _this$props2 = this.props,
          backgroundColor = _this$props2.backgroundColor,
          attributes = _this$props2.attributes,
          gradientValue = _this$props2.gradientValue;
      var customGradient = attributes.customGradient;

      if (customGradient || gradientValue) {
        return customGradient || gradientValue;
      }

      var colorAndStyleProps = (0, _colorProps.default)(attributes);
      return ((_colorAndStyleProps$s = colorAndStyleProps.style) === null || _colorAndStyleProps$s === void 0 ? void 0 : _colorAndStyleProps$s.backgroundColor) || ((_colorAndStyleProps$s2 = colorAndStyleProps.style) === null || _colorAndStyleProps$s2 === void 0 ? void 0 : _colorAndStyleProps$s2.background) || // We still need the `backgroundColor.color` to support colors from the color pallete (not custom ones)
      backgroundColor.color || _editor.default.defaultButton.backgroundColor;
    }
  }, {
    key: "getTextColor",
    value: function getTextColor() {
      var _colorAndStyleProps$s3;

      var _this$props3 = this.props,
          textColor = _this$props3.textColor,
          attributes = _this$props3.attributes;
      var colorAndStyleProps = (0, _colorProps.default)(attributes);
      return ((_colorAndStyleProps$s3 = colorAndStyleProps.style) === null || _colorAndStyleProps$s3 === void 0 ? void 0 : _colorAndStyleProps$s3.color) || // We still need the `textColor.color` to support colors from the color pallete (not custom ones)
      textColor.color || _editor.default.defaultButton.color;
    }
  }, {
    key: "onChangeText",
    value: function onChangeText(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        text: value
      });
    }
  }, {
    key: "onChangeBorderRadius",
    value: function onChangeBorderRadius(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        borderRadius: value
      });
    }
  }, {
    key: "onShowLinkSettings",
    value: function onShowLinkSettings() {
      this.setState({
        isLinkSheetVisible: true
      });
    }
  }, {
    key: "onHideLinkSettings",
    value: function onHideLinkSettings() {
      this.setState({
        isLinkSheetVisible: false
      });
    }
  }, {
    key: "onToggleButtonFocus",
    value: function onToggleButtonFocus(value) {
      this.setState({
        isButtonFocused: value
      });
    }
  }, {
    key: "onClearSettings",
    value: function onClearSettings() {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        url: '',
        rel: '',
        linkTarget: ''
      });
      this.onHideLinkSettings();
    }
  }, {
    key: "onLayout",
    value: function onLayout(_ref) {
      var nativeEvent = _ref.nativeEvent;
      var width = nativeEvent.layout.width;
      this.onSetMaxWidth(width);
    }
  }, {
    key: "onSetMaxWidth",
    value: function onSetMaxWidth(width) {
      var maxWidth = this.state.maxWidth;
      var parentWidth = this.props.parentWidth;
      var spacing = _editor.default.defaultButton.marginRight;
      var isParentWidthChanged = maxWidth !== parentWidth;
      var isWidthChanged = maxWidth !== width;

      if (parentWidth && !width && isParentWidthChanged) {
        this.setState({
          maxWidth: parentWidth
        });
      } else if (!parentWidth && width && isWidthChanged) {
        this.setState({
          maxWidth: width - spacing
        });
      }
    }
  }, {
    key: "onRemove",
    value: function onRemove() {
      var _this$props4 = this.props,
          numOfButtons = _this$props4.numOfButtons,
          onDeleteBlock = _this$props4.onDeleteBlock,
          onReplace = _this$props4.onReplace;

      if (numOfButtons === 1) {
        onDeleteBlock();
      } else {
        onReplace([]);
      }
    }
  }, {
    key: "dismissSheet",
    value: function dismissSheet() {
      this.onHideLinkSettings();
      this.props.closeSettingsBottomSheet();
    }
  }, {
    key: "getLinkSettings",
    value: function getLinkSettings(isCompatibleWithSettings) {
      var isLinkSheetVisible = this.state.isLinkSheetVisible;
      var _this$props5 = this.props,
          attributes = _this$props5.attributes,
          setAttributes = _this$props5.setAttributes;
      var actions = [{
        label: (0, _i18n.__)('Remove link'),
        onPress: this.onClearSettings
      }];
      var options = {
        url: {
          label: (0, _i18n.__)('Button Link URL'),
          placeholder: (0, _i18n.__)('Add URL'),
          autoFocus: !isCompatibleWithSettings,
          autoFill: true
        },
        openInNewTab: {
          label: (0, _i18n.__)('Open in new tab')
        },
        linkRel: {
          label: (0, _i18n.__)('Link Rel'),
          placeholder: (0, _i18n.__)('None')
        }
      };
      return (0, _element.createElement)(_components.LinkSettings, {
        isVisible: isLinkSheetVisible,
        attributes: attributes,
        onClose: this.dismissSheet,
        setAttributes: setAttributes,
        withBottomSheet: !isCompatibleWithSettings,
        actions: actions,
        options: options,
        showIcon: !isCompatibleWithSettings
      });
    }
  }, {
    key: "setRef",
    value: function setRef(richText) {
      this.richTextRef = richText;
    } // Render `Text` with `placeholderText` styled as a placeholder
    // to calculate its width which then is set as a `minWidth`

  }, {
    key: "getPlaceholderWidth",
    value: function getPlaceholderWidth(placeholderText) {
      var _this3 = this;

      var _this$state2 = this.state,
          maxWidth = _this$state2.maxWidth,
          placeholderTextWidth = _this$state2.placeholderTextWidth;
      return (0, _element.createElement)(_reactNative.Text, {
        style: _editor.default.placeholder,
        onTextLayout: function onTextLayout(_ref2) {
          var nativeEvent = _ref2.nativeEvent;
          var textWidth = nativeEvent.lines[0] && nativeEvent.lines[0].width;

          if (textWidth && textWidth !== placeholderTextWidth) {
            _this3.setState({
              placeholderTextWidth: Math.min(textWidth, maxWidth)
            });
          }
        }
      }, placeholderText);
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var _this$props6 = this.props,
          attributes = _this$props6.attributes,
          isSelected = _this$props6.isSelected,
          clientId = _this$props6.clientId,
          onReplace = _this$props6.onReplace,
          mergeBlocks = _this$props6.mergeBlocks,
          parentWidth = _this$props6.parentWidth;
      var placeholder = attributes.placeholder,
          text = attributes.text,
          borderRadius = attributes.borderRadius,
          url = attributes.url;
      var _this$state3 = this.state,
          maxWidth = _this$state3.maxWidth,
          isButtonFocused = _this$state3.isButtonFocused,
          placeholderTextWidth = _this$state3.placeholderTextWidth;
      var _styles$defaultButton = _editor.default.defaultButton,
          spacing = _styles$defaultButton.paddingTop,
          borderWidth = _styles$defaultButton.borderWidth;

      if (parentWidth === 0) {
        return null;
      }

      var borderRadiusValue = Number.isInteger(borderRadius) ? borderRadius : _editor.default.defaultButton.borderRadius;
      var outlineBorderRadius = borderRadiusValue > 0 ? borderRadiusValue + spacing + borderWidth : 0; // To achieve proper expanding and shrinking `RichText` on iOS, there is a need to set a `minWidth`
      // value at least on 1 when `RichText` is focused or when is not focused, but `RichText` value is
      // different than empty string.

      var minWidth = isButtonFocused || !isButtonFocused && text && text !== '' ? 1 : placeholderTextWidth; // To achieve proper expanding and shrinking `RichText` on Android, there is a need to set
      // a `placeholder` as an empty string when `RichText` is focused,
      // because `AztecView` is calculating a `minWidth` based on placeholder text.

      var placeholderText = isButtonFocused || !isButtonFocused && text && text !== '' ? '' : placeholder || (0, _i18n.__)('Add text…');
      var backgroundColor = this.getBackgroundColor();
      var textColor = this.getTextColor();
      return (0, _element.createElement)(_reactNative.View, {
        onLayout: this.onLayout
      }, this.getPlaceholderWidth(placeholderText), (0, _element.createElement)(_colorBackground.default, {
        borderRadiusValue: borderRadiusValue,
        backgroundColor: backgroundColor,
        isSelected: isSelected
      }, isSelected && (0, _element.createElement)(_reactNative.View, {
        pointerEvents: "none",
        style: [_editor.default.outline, {
          borderRadius: outlineBorderRadius,
          borderColor: backgroundColor
        }]
      }), (0, _element.createElement)(_blockEditor.RichText, {
        setRef: this.setRef,
        placeholder: placeholderText,
        value: text,
        onChange: this.onChangeText,
        style: _objectSpread(_objectSpread({}, _richText.default.richText), {}, {
          color: textColor
        }),
        textAlign: "center",
        placeholderTextColor: _editor.default.placeholderTextColor.color,
        identifier: "text",
        tagName: "p",
        minWidth: minWidth,
        maxWidth: maxWidth,
        id: clientId,
        isSelected: isButtonFocused,
        withoutInteractiveFormatting: true,
        unstableOnFocus: function unstableOnFocus() {
          return _this4.onToggleButtonFocus(true);
        },
        __unstableMobileNoFocusOnMount: !isSelected,
        selectionColor: textColor,
        onBlur: function onBlur() {
          _this4.onSetMaxWidth();
        },
        onReplace: onReplace,
        onRemove: this.onRemove,
        onMerge: mergeBlocks
      })), isSelected && (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
        title: (0, _i18n.__)('Edit link'),
        icon: _icons.link,
        onClick: this.onShowLinkSettings,
        isActive: url
      }))), this.getLinkSettings(false), (0, _element.createElement)(_colorEdit.default, this.props), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Border Settings')
      }, (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Border Radius'),
        minimumValue: MIN_BORDER_RADIUS_VALUE,
        maximumValue: MAX_BORDER_RADIUS_VALUE,
        value: borderRadiusValue,
        onChange: this.onChangeBorderRadius
      })), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Link Settings')
      }, this.getLinkSettings(true))));
    }
  }]);
  return ButtonEdit;
}(_element.Component);

var _default = (0, _compose.compose)([_compose.withInstanceId, _blockEditor.withGradient, (0, _blockEditor.withColors)('backgroundColor', {
  textColor: 'color'
}), (0, _data.withSelect)(function (select, _ref3) {
  var clientId = _ref3.clientId;

  var _select = select('core/edit-post'),
      isEditorSidebarOpened = _select.isEditorSidebarOpened;

  var _select2 = select('core/block-editor'),
      getSelectedBlockClientId = _select2.getSelectedBlockClientId,
      getBlockCount = _select2.getBlockCount,
      getBlockRootClientId = _select2.getBlockRootClientId;

  var parentId = getBlockRootClientId(clientId);
  var selectedId = getSelectedBlockClientId();
  var numOfButtons = getBlockCount(parentId);
  return {
    selectedId: selectedId,
    editorSidebarOpened: isEditorSidebarOpened(),
    numOfButtons: numOfButtons
  };
}), (0, _data.withDispatch)(function (dispatch) {
  return {
    closeSettingsBottomSheet: function closeSettingsBottomSheet() {
      dispatch('core/edit-post').closeGeneralSidebar();
    }
  };
})])(ButtonEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map