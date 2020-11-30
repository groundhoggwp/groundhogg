"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "imageFillStyles", {
  enumerable: true,
  get: function get() {
    return _mediaContainer.imageFillStyles;
  }
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

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _url = require("@wordpress/url");

var _compose = require("@wordpress/compose");

var _style = _interopRequireDefault(require("./style.scss"));

var _mediaContainerIcon = _interopRequireDefault(require("./media-container-icon"));

var _iconRetry = _interopRequireDefault(require("./icon-retry"));

var _mediaContainer = require("./media-container.js");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * Constants
 */
var ALLOWED_MEDIA_TYPES = [_blockEditor.MEDIA_TYPE_IMAGE, _blockEditor.MEDIA_TYPE_VIDEO];
var ICON_TYPE = {
  PLACEHOLDER: 'placeholder',
  RETRY: 'retry'
};

var MediaContainer = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(MediaContainer, _Component);

  var _super = _createSuper(MediaContainer);

  function MediaContainer() {
    var _this;

    (0, _classCallCheck2.default)(this, MediaContainer);
    _this = _super.apply(this, arguments);
    _this.onUploadError = _this.onUploadError.bind((0, _assertThisInitialized2.default)(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind((0, _assertThisInitialized2.default)(_this));
    _this.mediaUploadStateReset = _this.mediaUploadStateReset.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSelectMediaUploadOption = _this.onSelectMediaUploadOption.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMediaPressed = _this.onMediaPressed.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      isUploadInProgress: false
    };
    return _this;
  }

  (0, _createClass2.default)(MediaContainer, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          mediaId = _this$props.mediaId,
          mediaUrl = _this$props.mediaUrl; // Make sure we mark any temporary images as failed if they failed while
      // the editor wasn't open

      if (mediaId && mediaUrl && (0, _url.getProtocol)(mediaUrl) === 'file:') {
        (0, _reactNativeBridge.mediaUploadSync)();
      }
    }
  }, {
    key: "onUploadError",
    value: function onUploadError(message) {
      var noticeOperations = this.props.noticeOperations;
      noticeOperations.removeAllNotices();
      noticeOperations.createErrorNotice(message);
    }
  }, {
    key: "onSelectMediaUploadOption",
    value: function onSelectMediaUploadOption(params) {
      var id = params.id,
          url = params.url,
          type = params.type;
      var onSelectMedia = this.props.onSelectMedia;
      onSelectMedia({
        media_type: type,
        id: id,
        url: url
      });
    }
  }, {
    key: "onMediaPressed",
    value: function onMediaPressed() {
      var isUploadInProgress = this.state.isUploadInProgress;
      var _this$props2 = this.props,
          mediaId = _this$props2.mediaId,
          mediaUrl = _this$props2.mediaUrl,
          mediaType = _this$props2.mediaType,
          isMediaSelected = _this$props2.isMediaSelected,
          onMediaSelected = _this$props2.onMediaSelected;

      if (isUploadInProgress) {
        (0, _reactNativeBridge.requestImageUploadCancelDialog)(mediaId);
      } else if (mediaId && (0, _url.getProtocol)(mediaUrl) === 'file:') {
        (0, _reactNativeBridge.requestImageFailedRetryDialog)(mediaId);
      } else if (mediaType === _blockEditor.MEDIA_TYPE_IMAGE && isMediaSelected) {
        (0, _reactNativeBridge.requestImageFullscreenPreview)(mediaUrl);
      } else if (mediaType === _blockEditor.MEDIA_TYPE_IMAGE) {
        onMediaSelected();
      }
    }
  }, {
    key: "getIcon",
    value: function getIcon(iconType) {
      var _this$props3 = this.props,
          mediaType = _this$props3.mediaType,
          getStylesFromColorScheme = _this$props3.getStylesFromColorScheme;
      var iconStyle;

      switch (iconType) {
        case ICON_TYPE.RETRY:
          iconStyle = mediaType === _blockEditor.MEDIA_TYPE_IMAGE ? _style.default.iconRetry : getStylesFromColorScheme(_style.default.iconRetryVideo, _style.default.iconRetryVideoDark);
          return (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
            icon: _iconRetry.default
          }, iconStyle));

        case ICON_TYPE.PLACEHOLDER:
          iconStyle = getStylesFromColorScheme(_style.default.iconPlaceholder, _style.default.iconPlaceholderDark);
          break;
      }

      return (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
        icon: _mediaContainerIcon.default
      }, iconStyle));
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
      var onMediaUpdate = this.props.onMediaUpdate;
      onMediaUpdate({
        id: payload.mediaServerId,
        url: payload.mediaUrl
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "finishMediaUploadWithFailure",
    value: function finishMediaUploadWithFailure() {
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "mediaUploadStateReset",
    value: function mediaUploadStateReset() {
      var onMediaUpdate = this.props.onMediaUpdate;
      onMediaUpdate({
        id: null,
        url: null
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "renderImage",
    value: function renderImage(params, openMediaOptions) {
      var isUploadInProgress = this.state.isUploadInProgress;
      var _this$props4 = this.props,
          aligmentStyles = _this$props4.aligmentStyles,
          focalPoint = _this$props4.focalPoint,
          imageFill = _this$props4.imageFill,
          isMediaSelected = _this$props4.isMediaSelected,
          isSelected = _this$props4.isSelected,
          mediaAlt = _this$props4.mediaAlt,
          mediaUrl = _this$props4.mediaUrl,
          mediaWidth = _this$props4.mediaWidth,
          shouldStack = _this$props4.shouldStack;
      var isUploadFailed = params.isUploadFailed,
          retryMessage = params.retryMessage;
      var focalPointValues = !focalPoint ? _components.IMAGE_DEFAULT_FOCAL_POINT : focalPoint;
      return (0, _element.createElement)(_reactNative.View, {
        style: [imageFill && _style.default.imageWithFocalPoint, imageFill && shouldStack && {
          height: _style.default.imageFill.height
        }]
      }, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onMediaPressed,
        onLongPress: openMediaOptions,
        disabled: !isSelected
      }, (0, _element.createElement)(_reactNative.View, {
        style: [imageFill && _style.default.imageCropped, _style.default.mediaImageContainer, !isUploadInProgress && aligmentStyles]
      }, (0, _element.createElement)(_components.Image, {
        align: "center",
        alt: mediaAlt,
        focalPoint: imageFill && focalPointValues,
        isSelected: isMediaSelected,
        isUploadFailed: isUploadFailed,
        isUploadInProgress: isUploadInProgress,
        onSelectMediaUploadOption: this.onSelectMediaUploadOption,
        openMediaOptions: openMediaOptions,
        retryMessage: retryMessage,
        url: mediaUrl,
        width: !isUploadInProgress && mediaWidth
      }))));
    }
  }, {
    key: "renderVideo",
    value: function renderVideo(params, openMediaOptions) {
      var _this$props5 = this.props,
          aligmentStyles = _this$props5.aligmentStyles,
          mediaUrl = _this$props5.mediaUrl,
          isSelected = _this$props5.isSelected,
          getStylesFromColorScheme = _this$props5.getStylesFromColorScheme;
      var isUploadInProgress = this.state.isUploadInProgress;
      var isUploadFailed = params.isUploadFailed,
          retryMessage = params.retryMessage;
      var showVideo = (0, _url.isURL)(mediaUrl) && !isUploadInProgress && !isUploadFailed;
      var videoPlaceholderStyles = getStylesFromColorScheme(_style.default.videoPlaceholder, _style.default.videoPlaceholderDark);
      var retryVideoTextStyles = [_style.default.uploadFailedText, getStylesFromColorScheme(_style.default.uploadFailedTextVideo, _style.default.uploadFailedTextVideoDark)];
      return (0, _element.createElement)(_reactNative.View, {
        style: _style.default.mediaVideo
      }, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onMediaPressed,
        onLongPress: openMediaOptions,
        disabled: !isSelected
      }, (0, _element.createElement)(_reactNative.View, {
        style: [_style.default.videoContainer, aligmentStyles]
      }, (0, _element.createElement)(_reactNative.View, {
        style: [_style.default.videoContent, {
          aspectRatio: _blockEditor.VIDEO_ASPECT_RATIO
        }]
      }, showVideo && (0, _element.createElement)(_reactNative.View, {
        style: _style.default.videoPlayer
      }, (0, _element.createElement)(_blockEditor.VideoPlayer, {
        isSelected: isSelected,
        style: _style.default.video,
        source: {
          uri: mediaUrl
        },
        paused: true
      })), !showVideo && (0, _element.createElement)(_reactNative.View, {
        style: videoPlaceholderStyles
      }, (0, _element.createElement)(_reactNative.View, {
        style: _style.default.modalIcon
      }, isUploadFailed ? this.getIcon(ICON_TYPE.RETRY) : this.getIcon(ICON_TYPE.PLACEHOLDER)), isUploadFailed && (0, _element.createElement)(_reactNative.Text, {
        style: retryVideoTextStyles
      }, retryMessage))))));
    }
  }, {
    key: "renderContent",
    value: function renderContent(params, openMediaOptions) {
      var mediaType = this.props.mediaType;
      var mediaElement = null;

      switch (mediaType) {
        case _blockEditor.MEDIA_TYPE_IMAGE:
          mediaElement = this.renderImage(params, openMediaOptions);
          break;

        case _blockEditor.MEDIA_TYPE_VIDEO:
          mediaElement = this.renderVideo(params, openMediaOptions);
          break;
      }

      return mediaElement;
    }
  }, {
    key: "renderPlaceholder",
    value: function renderPlaceholder() {
      return (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
        icon: this.getIcon(ICON_TYPE.PLACEHOLDER),
        labels: {
          title: (0, _i18n.__)('Media area')
        },
        onSelect: this.onSelectMediaUploadOption,
        allowedTypes: ALLOWED_MEDIA_TYPES,
        onFocus: this.props.onFocus,
        onError: this.onUploadError
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props6 = this.props,
          mediaUrl = _this$props6.mediaUrl,
          mediaId = _this$props6.mediaId,
          mediaType = _this$props6.mediaType,
          onSetOpenPickerRef = _this$props6.onSetOpenPickerRef;
      var coverUrl = mediaType === _blockEditor.MEDIA_TYPE_IMAGE ? mediaUrl : null;

      if (mediaUrl) {
        return (0, _element.createElement)(_blockEditor.MediaUpload, {
          isReplacingMedia: true,
          onSelect: this.onSelectMediaUploadOption,
          allowedTypes: ALLOWED_MEDIA_TYPES,
          value: mediaId,
          render: function render(_ref) {
            var open = _ref.open,
                getMediaOptions = _ref.getMediaOptions;
            onSetOpenPickerRef(open);
            return (0, _element.createElement)(_element.Fragment, null, getMediaOptions(), (0, _element.createElement)(_blockEditor.MediaUploadProgress, {
              coverUrl: coverUrl,
              mediaId: mediaId,
              onUpdateMediaProgress: _this2.updateMediaProgress,
              onFinishMediaUploadWithSuccess: _this2.finishMediaUploadWithSuccess,
              onFinishMediaUploadWithFailure: _this2.finishMediaUploadWithFailure,
              onMediaUploadStateReset: _this2.mediaUploadStateReset,
              renderContent: function renderContent(params) {
                return _this2.renderContent(params, open);
              }
            }));
          }
        });
      }

      return this.renderPlaceholder();
    }
  }]);
  return MediaContainer;
}(_element.Component);

var _default = (0, _compose.compose)(_components.withNotices, _compose.withPreferredColorScheme)(MediaContainer);

exports.default = _default;
//# sourceMappingURL=media-container.native.js.map