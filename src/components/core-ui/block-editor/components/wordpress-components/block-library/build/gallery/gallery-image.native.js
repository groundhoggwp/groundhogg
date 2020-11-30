"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _url = require("@wordpress/url");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _galleryButton = _interopRequireDefault(require("./gallery-button"));

var _galleryImageStyle = _interopRequireDefault(require("./gallery-image-style.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var compose = _reactNative.StyleSheet.compose;
var separatorStyle = compose(_galleryImageStyle.default.separator, {
  borderRightWidth: _reactNative.StyleSheet.hairlineWidth
});
var buttonStyle = compose(_galleryImageStyle.default.button, {
  aspectRatio: 1
});
var ICON_SIZE_ARROW = 15;

var GalleryImage = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(GalleryImage, _Component);

  var _super = _createSuper(GalleryImage);

  function GalleryImage() {
    var _this;

    (0, _classCallCheck2.default)(this, GalleryImage);
    _this = _super.apply(this, arguments);
    _this.onSelectImage = _this.onSelectImage.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSelectCaption = _this.onSelectCaption.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMediaPressed = _this.onMediaPressed.bind((0, _assertThisInitialized2.default)(_this));
    _this.onCaptionChange = _this.onCaptionChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSelectMedia = _this.onSelectMedia.bind((0, _assertThisInitialized2.default)(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderContent = _this.renderContent.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      captionSelected: false,
      isUploadInProgress: false,
      didUploadFail: false
    };
    return _this;
  }

  (0, _createClass2.default)(GalleryImage, [{
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
        (0, _reactNativeBridge.requestImageUploadCancelDialog)(id);
      } else if (didUploadFail || id && (0, _url.getProtocol)(url) === 'file:') {
        (0, _reactNativeBridge.requestImageFailedRetryDialog)(id);
      } else if (isSelected && !captionSelected) {
        (0, _reactNativeBridge.requestImageFullscreenPreview)(url);
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
      var captionPlaceholderStyle = getStylesFromColorScheme(_galleryImageStyle.default.captionPlaceholder, _galleryImageStyle.default.captionPlaceholderDark);
      var shouldShowCaptionEditable = !isUploadFailed && isSelected;
      var shouldShowCaptionExpanded = !isUploadFailed && !isSelected && !!caption;
      var captionContainerStyle = shouldShowCaptionExpanded ? _galleryImageStyle.default.captionExpandedContainer : _galleryImageStyle.default.captionContainer;
      var captionStyle = shouldShowCaptionExpanded ? _galleryImageStyle.default.captionExpanded : _galleryImageStyle.default.caption;
      var mediaPickerOptions = [{
        destructiveButton: true,
        id: 'removeImage',
        label: (0, _i18n.__)('Remove'),
        onPress: onRemove,
        separated: true,
        value: 'removeImage'
      }];
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.Image, {
        alt: ariaLabel,
        height: _galleryImageStyle.default.image.height,
        isSelected: isSelected,
        isUploadFailed: isUploadFailed,
        isUploadInProgress: isUploadInProgress,
        mediaPickerOptions: mediaPickerOptions,
        onSelectMediaUploadOption: this.onSelectMedia,
        resizeMode: resizeMode,
        url: url
      }), isUploadFailed && (0, _element.createElement)(_reactNative.View, {
        style: _galleryImageStyle.default.uploadFailedContainer
      }, (0, _element.createElement)(_reactNative.View, {
        style: _galleryImageStyle.default.uploadFailed
      }, (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
        icon: _icons.warning
      }, _galleryImageStyle.default.uploadFailedIcon))), (0, _element.createElement)(_reactNative.Text, {
        style: _galleryImageStyle.default.uploadFailedText
      }, retryMessage)), !isUploadInProgress && isSelected && (0, _element.createElement)(_reactNative.View, {
        style: _galleryImageStyle.default.toolbarContainer
      }, (0, _element.createElement)(_reactNative.View, {
        style: _galleryImageStyle.default.toolbar
      }, (0, _element.createElement)(_reactNative.View, {
        style: _galleryImageStyle.default.moverButtonContainer
      }, (0, _element.createElement)(_galleryButton.default, {
        style: buttonStyle,
        icon: isRTL ? _icons.arrowRight : _icons.arrowLeft,
        iconSize: ICON_SIZE_ARROW,
        onClick: isFirstItem ? undefined : onMoveBackward,
        accessibilityLabel: (0, _i18n.__)('Move Image Backward'),
        "aria-disabled": isFirstItem,
        disabled: !isSelected
      }), (0, _element.createElement)(_reactNative.View, {
        style: separatorStyle
      }), (0, _element.createElement)(_galleryButton.default, {
        style: buttonStyle,
        icon: isRTL ? _icons.arrowLeft : _icons.arrowRight,
        iconSize: ICON_SIZE_ARROW,
        onClick: isLastItem ? undefined : onMoveForward,
        accessibilityLabel: (0, _i18n.__)('Move Image Forward'),
        "aria-disabled": isLastItem,
        disabled: !isSelected
      })))), !isUploadInProgress && (shouldShowCaptionEditable || shouldShowCaptionExpanded) && (0, _element.createElement)(_reactNative.View, {
        style: captionContainerStyle
      }, (0, _element.createElement)(_reactNative.ScrollView, {
        nestedScrollEnabled: true,
        keyboardShouldPersistTaps: "handled",
        bounces: false
      }, (0, _element.createElement)(_blockEditor.Caption, {
        inlineToolbar: true,
        isSelected: captionSelected,
        onChange: this.onCaptionChange,
        onFocus: this.onSelectCaption,
        placeholder: isSelected ? (0, _i18n.__)('Write caption…') : null,
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
      var containerStyle = getStylesFromColorScheme(_galleryImageStyle.default.galleryImageContainer, _galleryImageStyle.default.galleryImageContainerDark);
      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        onPress: this.onMediaPressed,
        accessible: !isSelected // We need only child views to be accessible after the selection
        ,
        accessibilityLabel: this.accessibilityLabelImageContainer() // if we don't set this explicitly it reads system provided accessibilityLabels of all child components and those include pretty technical words which don't make sense
        ,
        accessibilityRole: 'imagebutton' // this makes VoiceOver to read a description of image provided by system on iOS and lets user know this is a button which conveys the message of tappablity

      }, (0, _element.createElement)(_reactNative.View, {
        style: containerStyle
      }, (0, _element.createElement)(_blockEditor.MediaUploadProgress, {
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
      return (0, _lodash.isEmpty)(caption) ? ariaLabel : ariaLabel + '. ' + (0, _i18n.sprintf)(
      /* translators: accessibility text. %s: image caption. */
      (0, _i18n.__)('Image caption. %s'), caption);
    }
  }]);
  return GalleryImage;
}(_element.Component);

var _default = (0, _compose.withPreferredColorScheme)(GalleryImage);

exports.default = _default;
//# sourceMappingURL=gallery-image.native.js.map