import _extends from "@babel/runtime/helpers/esm/extends";
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
import { StyleSheet, View, ScrollView, Text, TouchableWithoutFeedback } from 'react-native';
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { requestImageFailedRetryDialog, requestImageUploadCancelDialog, requestImageFullscreenPreview } from '@wordpress/react-native-bridge';
import { Component } from '@wordpress/element';
import { Icon, Image } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { Caption, MediaUploadProgress } from '@wordpress/block-editor';
import { getProtocol } from '@wordpress/url';
import { withPreferredColorScheme } from '@wordpress/compose';
import { arrowLeft, arrowRight, warning } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import Button from './gallery-button';
import style from './gallery-image-style.scss';
var compose = StyleSheet.compose;
var separatorStyle = compose(style.separator, {
  borderRightWidth: StyleSheet.hairlineWidth
});
var buttonStyle = compose(style.button, {
  aspectRatio: 1
});
var ICON_SIZE_ARROW = 15;

var GalleryImage = /*#__PURE__*/function (_Component) {
  _inherits(GalleryImage, _Component);

  var _super = _createSuper(GalleryImage);

  function GalleryImage() {
    var _this;

    _classCallCheck(this, GalleryImage);

    _this = _super.apply(this, arguments);
    _this.onSelectImage = _this.onSelectImage.bind(_assertThisInitialized(_this));
    _this.onSelectCaption = _this.onSelectCaption.bind(_assertThisInitialized(_this));
    _this.onMediaPressed = _this.onMediaPressed.bind(_assertThisInitialized(_this));
    _this.onCaptionChange = _this.onCaptionChange.bind(_assertThisInitialized(_this));
    _this.onSelectMedia = _this.onSelectMedia.bind(_assertThisInitialized(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind(_assertThisInitialized(_this));
    _this.renderContent = _this.renderContent.bind(_assertThisInitialized(_this));
    _this.state = {
      captionSelected: false,
      isUploadInProgress: false,
      didUploadFail: false
    };
    return _this;
  }

  _createClass(GalleryImage, [{
    key: "onSelectCaption",
    value: function onSelectCaption() {
      if (!this.state.captionSelected) {
        this.setState({
          captionSelected: true
        });
      }

      if (!this.props.isSelected) {
        this.props.onSelect();
      }
    }
  }, {
    key: "onMediaPressed",
    value: function onMediaPressed() {
      var _this$props = this.props,
          id = _this$props.id,
          url = _this$props.url,
          isSelected = _this$props.isSelected;
      var _this$state = this.state,
          captionSelected = _this$state.captionSelected,
          isUploadInProgress = _this$state.isUploadInProgress,
          didUploadFail = _this$state.didUploadFail;
      this.onSelectImage();

      if (isUploadInProgress) {
        requestImageUploadCancelDialog(id);
      } else if (didUploadFail || id && getProtocol(url) === 'file:') {
        requestImageFailedRetryDialog(id);
      } else if (isSelected && !captionSelected) {
        requestImageFullscreenPreview(url);
      }
    }
  }, {
    key: "onSelectImage",
    value: function onSelectImage() {
      if (!this.props.isBlockSelected) {
        this.props.onSelectBlock();
      }

      if (!this.props.isSelected) {
        this.props.onSelect();
      }

      if (this.state.captionSelected) {
        this.setState({
          captionSelected: false
        });
      }
    }
  }, {
    key: "onSelectMedia",
    value: function onSelectMedia(media) {
      var setAttributes = this.props.setAttributes;
      setAttributes(media);
    }
  }, {
    key: "onCaptionChange",
    value: function onCaptionChange(caption) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        caption: caption
      });
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props2 = this.props,
          isSelected = _this$props2.isSelected,
          image = _this$props2.image,
          url = _this$props2.url;

      if (image && !url) {
        this.props.setAttributes({
          url: image.source_url,
          alt: image.alt_text
        });
      } // unselect the caption so when the user selects other image and comeback
      // the caption is not immediately selected


      if (this.state.captionSelected && !isSelected && prevProps.isSelected) {
        this.setState({
          captionSelected: false
        });
      }
    }
  }, {
    key: "updateMediaProgress",
    value: function updateMediaProgress() {
      if (!this.state.isUploadInProgress) {
        this.setState({
          isUploadInProgress: true
        });
      }
    }
  }, {
    key: "finishMediaUploadWithSuccess",
    value: function finishMediaUploadWithSuccess(payload) {
      this.setState({
        isUploadInProgress: false,
        didUploadFail: false
      });
      this.props.setAttributes({
        id: payload.mediaServerId,
        url: payload.mediaUrl
      });
    }
  }, {
    key: "finishMediaUploadWithFailure",
    value: function finishMediaUploadWithFailure() {
      this.setState({
        isUploadInProgress: false,
        didUploadFail: true
      });
    }
  }, {
    key: "renderContent",
    value: function renderContent(params) {
      var _this$props3 = this.props,
          url = _this$props3.url,
          isFirstItem = _this$props3.isFirstItem,
          isLastItem = _this$props3.isLastItem,
          isSelected = _this$props3.isSelected,
          caption = _this$props3.caption,
          onRemove = _this$props3.onRemove,
          onMoveForward = _this$props3.onMoveForward,
          onMoveBackward = _this$props3.onMoveBackward,
          ariaLabel = _this$props3['aria-label'],
          isCropped = _this$props3.isCropped,
          getStylesFromColorScheme = _this$props3.getStylesFromColorScheme,
          isRTL = _this$props3.isRTL;
      var _this$state2 = this.state,
          isUploadInProgress = _this$state2.isUploadInProgress,
          captionSelected = _this$state2.captionSelected;
      var isUploadFailed = params.isUploadFailed,
          retryMessage = params.retryMessage;
      var resizeMode = isCropped ? 'cover' : 'contain';
      var captionPlaceholderStyle = getStylesFromColorScheme(style.captionPlaceholder, style.captionPlaceholderDark);
      var shouldShowCaptionEditable = !isUploadFailed && isSelected;
      var shouldShowCaptionExpanded = !isUploadFailed && !isSelected && !!caption;
      var captionContainerStyle = shouldShowCaptionExpanded ? style.captionExpandedContainer : style.captionContainer;
      var captionStyle = shouldShowCaptionExpanded ? style.captionExpanded : style.caption;
      var mediaPickerOptions = [{
        destructiveButton: true,
        id: 'removeImage',
        label: __('Remove'),
        onPress: onRemove,
        separated: true,
        value: 'removeImage'
      }];
      return createElement(Fragment, null, createElement(Image, {
        alt: ariaLabel,
        height: style.image.height,
        isSelected: isSelected,
        isUploadFailed: isUploadFailed,
        isUploadInProgress: isUploadInProgress,
        mediaPickerOptions: mediaPickerOptions,
        onSelectMediaUploadOption: this.onSelectMedia,
        resizeMode: resizeMode,
        url: url
      }), isUploadFailed && createElement(View, {
        style: style.uploadFailedContainer
      }, createElement(View, {
        style: style.uploadFailed
      }, createElement(Icon, _extends({
        icon: warning
      }, style.uploadFailedIcon))), createElement(Text, {
        style: style.uploadFailedText
      }, retryMessage)), !isUploadInProgress && isSelected && createElement(View, {
        style: style.toolbarContainer
      }, createElement(View, {
        style: style.toolbar
      }, createElement(View, {
        style: style.moverButtonContainer
      }, createElement(Button, {
        style: buttonStyle,
        icon: isRTL ? arrowRight : arrowLeft,
        iconSize: ICON_SIZE_ARROW,
        onClick: isFirstItem ? undefined : onMoveBackward,
        accessibilityLabel: __('Move Image Backward'),
        "aria-disabled": isFirstItem,
        disabled: !isSelected
      }), createElement(View, {
        style: separatorStyle
      }), createElement(Button, {
        style: buttonStyle,
        icon: isRTL ? arrowLeft : arrowRight,
        iconSize: ICON_SIZE_ARROW,
        onClick: isLastItem ? undefined : onMoveForward,
        accessibilityLabel: __('Move Image Forward'),
        "aria-disabled": isLastItem,
        disabled: !isSelected
      })))), !isUploadInProgress && (shouldShowCaptionEditable || shouldShowCaptionExpanded) && createElement(View, {
        style: captionContainerStyle
      }, createElement(ScrollView, {
        nestedScrollEnabled: true,
        keyboardShouldPersistTaps: "handled",
        bounces: false
      }, createElement(Caption, {
        inlineToolbar: true,
        isSelected: captionSelected,
        onChange: this.onCaptionChange,
        onFocus: this.onSelectCaption,
        placeholder: isSelected ? __('Write caption…') : null,
        placeholderTextColor: captionPlaceholderStyle.color,
        style: captionStyle,
        value: caption
      }))));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          id = _this$props4.id,
          onRemove = _this$props4.onRemove,
          getStylesFromColorScheme = _this$props4.getStylesFromColorScheme,
          isSelected = _this$props4.isSelected;
      var containerStyle = getStylesFromColorScheme(style.galleryImageContainer, style.galleryImageContainerDark);
      return createElement(TouchableWithoutFeedback, {
        onPress: this.onMediaPressed,
        accessible: !isSelected // We need only child views to be accessible after the selection
        ,
        accessibilityLabel: this.accessibilityLabelImageContainer() // if we don't set this explicitly it reads system provided accessibilityLabels of all child components and those include pretty technical words which don't make sense
        ,
        accessibilityRole: 'imagebutton' // this makes VoiceOver to read a description of image provided by system on iOS and lets user know this is a button which conveys the message of tappablity

      }, createElement(View, {
        style: containerStyle
      }, createElement(MediaUploadProgress, {
        mediaId: id,
        onUpdateMediaProgress: this.updateMediaProgress,
        onFinishMediaUploadWithSuccess: this.finishMediaUploadWithSuccess,
        onFinishMediaUploadWithFailure: this.finishMediaUploadWithFailure,
        onMediaUploadStateReset: onRemove,
        renderContent: this.renderContent
      })));
    }
  }, {
    key: "accessibilityLabelImageContainer",
    value: function accessibilityLabelImageContainer() {
      var _this$props5 = this.props,
          caption = _this$props5.caption,
          ariaLabel = _this$props5['aria-label'];
      return isEmpty(caption) ? ariaLabel : ariaLabel + '. ' + sprintf(
      /* translators: accessibility text. %s: image caption. */
      __('Image caption. %s'), caption);
    }
  }]);

  return GalleryImage;
}(Component);

export default withPreferredColorScheme(GalleryImage);
//# sourceMappingURL=gallery-image.native.js.map