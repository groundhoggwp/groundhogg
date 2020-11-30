import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { View, AccessibilityInfo, Platform, Text } from 'react-native';
/**
 * WordPress dependencies
 */

import { withInstanceId, compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { RichText, withColors, InspectorControls, BlockControls, withGradient } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToolbarGroup, ToolbarButton, LinkSettings } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { link } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import richTextStyle from './rich-text.scss';
import styles from './editor.scss';
import ColorBackground from './color-background';
import ColorEdit from './color-edit';
import getColorAndStyleProps from './color-props';
var MIN_BORDER_RADIUS_VALUE = 0;
var MAX_BORDER_RADIUS_VALUE = 50;
var INITIAL_MAX_WIDTH = 108;

var ButtonEdit = /*#__PURE__*/function (_Component) {
  _inherits(ButtonEdit, _Component);

  var _super = _createSuper(ButtonEdit);

  function ButtonEdit(props) {
    var _this;

    _classCallCheck(this, ButtonEdit);

    _this = _super.call(this, props);
    _this.onChangeText = _this.onChangeText.bind(_assertThisInitialized(_this));
    _this.onChangeBorderRadius = _this.onChangeBorderRadius.bind(_assertThisInitialized(_this));
    _this.onClearSettings = _this.onClearSettings.bind(_assertThisInitialized(_this));
    _this.onLayout = _this.onLayout.bind(_assertThisInitialized(_this));
    _this.onSetMaxWidth = _this.onSetMaxWidth.bind(_assertThisInitialized(_this));
    _this.dismissSheet = _this.dismissSheet.bind(_assertThisInitialized(_this));
    _this.onShowLinkSettings = _this.onShowLinkSettings.bind(_assertThisInitialized(_this));
    _this.onHideLinkSettings = _this.onHideLinkSettings.bind(_assertThisInitialized(_this));
    _this.onToggleButtonFocus = _this.onToggleButtonFocus.bind(_assertThisInitialized(_this));
    _this.setRef = _this.setRef.bind(_assertThisInitialized(_this));
    _this.onRemove = _this.onRemove.bind(_assertThisInitialized(_this));
    _this.getPlaceholderWidth = _this.getPlaceholderWidth.bind(_assertThisInitialized(_this));
    _this.state = {
      maxWidth: INITIAL_MAX_WIDTH,
      isLinkSheetVisible: false,
      isButtonFocused: true,
      placeholderTextWidth: 0
    };
    return _this;
  }

  _createClass(ButtonEdit, [{
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
        if (Platform.OS === 'android' && this.richTextRef) {
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
          AccessibilityInfo.isScreenReaderEnabled().then(function (enabled) {
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

      var colorAndStyleProps = getColorAndStyleProps(attributes);
      return ((_colorAndStyleProps$s = colorAndStyleProps.style) === null || _colorAndStyleProps$s === void 0 ? void 0 : _colorAndStyleProps$s.backgroundColor) || ((_colorAndStyleProps$s2 = colorAndStyleProps.style) === null || _colorAndStyleProps$s2 === void 0 ? void 0 : _colorAndStyleProps$s2.background) || // We still need the `backgroundColor.color` to support colors from the color pallete (not custom ones)
      backgroundColor.color || styles.defaultButton.backgroundColor;
    }
  }, {
    key: "getTextColor",
    value: function getTextColor() {
      var _colorAndStyleProps$s3;

      var _this$props3 = this.props,
          textColor = _this$props3.textColor,
          attributes = _this$props3.attributes;
      var colorAndStyleProps = getColorAndStyleProps(attributes);
      return ((_colorAndStyleProps$s3 = colorAndStyleProps.style) === null || _colorAndStyleProps$s3 === void 0 ? void 0 : _colorAndStyleProps$s3.color) || // We still need the `textColor.color` to support colors from the color pallete (not custom ones)
      textColor.color || styles.defaultButton.color;
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
      var spacing = styles.defaultButton.marginRight;
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
        label: __('Remove link'),
        onPress: this.onClearSettings
      }];
      var options = {
        url: {
          label: __('Button Link URL'),
          placeholder: __('Add URL'),
          autoFocus: !isCompatibleWithSettings,
          autoFill: true
        },
        openInNewTab: {
          label: __('Open in new tab')
        },
        linkRel: {
          label: __('Link Rel'),
          placeholder: __('None')
        }
      };
      return createElement(LinkSettings, {
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
      return createElement(Text, {
        style: styles.placeholder,
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
      var _styles$defaultButton = styles.defaultButton,
          spacing = _styles$defaultButton.paddingTop,
          borderWidth = _styles$defaultButton.borderWidth;

      if (parentWidth === 0) {
        return null;
      }

      var borderRadiusValue = Number.isInteger(borderRadius) ? borderRadius : styles.defaultButton.borderRadius;
      var outlineBorderRadius = borderRadiusValue > 0 ? borderRadiusValue + spacing + borderWidth : 0; // To achieve proper expanding and shrinking `RichText` on iOS, there is a need to set a `minWidth`
      // value at least on 1 when `RichText` is focused or when is not focused, but `RichText` value is
      // different than empty string.

      var minWidth = isButtonFocused || !isButtonFocused && text && text !== '' ? 1 : placeholderTextWidth; // To achieve proper expanding and shrinking `RichText` on Android, there is a need to set
      // a `placeholder` as an empty string when `RichText` is focused,
      // because `AztecView` is calculating a `minWidth` based on placeholder text.

      var placeholderText = isButtonFocused || !isButtonFocused && text && text !== '' ? '' : placeholder || __('Add text…');
      var backgroundColor = this.getBackgroundColor();
      var textColor = this.getTextColor();
      return createElement(View, {
        onLayout: this.onLayout
      }, this.getPlaceholderWidth(placeholderText), createElement(ColorBackground, {
        borderRadiusValue: borderRadiusValue,
        backgroundColor: backgroundColor,
        isSelected: isSelected
      }, isSelected && createElement(View, {
        pointerEvents: "none",
        style: [styles.outline, {
          borderRadius: outlineBorderRadius,
          borderColor: backgroundColor
        }]
      }), createElement(RichText, {
        setRef: this.setRef,
        placeholder: placeholderText,
        value: text,
        onChange: this.onChangeText,
        style: _objectSpread(_objectSpread({}, richTextStyle.richText), {}, {
          color: textColor
        }),
        textAlign: "center",
        placeholderTextColor: styles.placeholderTextColor.color,
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
      })), isSelected && createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
        title: __('Edit link'),
        icon: link,
        onClick: this.onShowLinkSettings,
        isActive: url
      }))), this.getLinkSettings(false), createElement(ColorEdit, this.props), createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Border Settings')
      }, createElement(RangeControl, {
        label: __('Border Radius'),
        minimumValue: MIN_BORDER_RADIUS_VALUE,
        maximumValue: MAX_BORDER_RADIUS_VALUE,
        value: borderRadiusValue,
        onChange: this.onChangeBorderRadius
      })), createElement(PanelBody, {
        title: __('Link Settings')
      }, this.getLinkSettings(true))));
    }
  }]);

  return ButtonEdit;
}(Component);

export default compose([withInstanceId, withGradient, withColors('backgroundColor', {
  textColor: 'color'
}), withSelect(function (select, _ref3) {
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
}), withDispatch(function (dispatch) {
  return {
    closeSettingsBottomSheet: function closeSettingsBottomSheet() {
      dispatch('core/edit-post').closeGeneralSidebar();
    }
  };
})])(ButtonEdit);
//# sourceMappingURL=edit.native.js.map