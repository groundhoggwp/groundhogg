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
import { View, Text, TouchableWithoutFeedback } from 'react-native';
/**
 * WordPress dependencies
 */

import { mediaUploadSync, requestImageFailedRetryDialog, requestImageUploadCancelDialog, requestImageFullscreenPreview } from '@wordpress/react-native-bridge';
import { Icon, Image, IMAGE_DEFAULT_FOCAL_POINT, withNotices } from '@wordpress/components';
import { MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO, MediaPlaceholder, MediaUpload, MediaUploadProgress, VIDEO_ASPECT_RATIO, VideoPlayer } from '@wordpress/block-editor';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { isURL, getProtocol } from '@wordpress/url';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import icon from './media-container-icon';
import SvgIconRetry from './icon-retry';
/**
 * Constants
 */

var ALLOWED_MEDIA_TYPES = [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO];
var ICON_TYPE = {
  PLACEHOLDER: 'placeholder',
  RETRY: 'retry'
};
export { imageFillStyles } from './media-container.js';

var MediaContainer = /*#__PURE__*/function (_Component) {
  _inherits(MediaContainer, _Component);

  var _super = _createSuper(MediaContainer);

  function MediaContainer() {
    var _this;

    _classCallCheck(this, MediaContainer);

    _this = _super.apply(this, arguments);
    _this.onUploadError = _this.onUploadError.bind(_assertThisInitialized(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind(_assertThisInitialized(_this));
    _this.mediaUploadStateReset = _this.mediaUploadStateReset.bind(_assertThisInitialized(_this));
    _this.onSelectMediaUploadOption = _this.onSelectMediaUploadOption.bind(_assertThisInitialized(_this));
    _this.onMediaPressed = _this.onMediaPressed.bind(_assertThisInitialized(_this));
    _this.state = {
      isUploadInProgress: false
    };
    return _this;
  }

  _createClass(MediaContainer, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          mediaId = _this$props.mediaId,
          mediaUrl = _this$props.mediaUrl; // Make sure we mark any temporary images as failed if they failed while
      // the editor wasn't open

      if (mediaId && mediaUrl && getProtocol(mediaUrl) === 'file:') {
        mediaUploadSync();
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
        requestImageUploadCancelDialog(mediaId);
      } else if (mediaId && getProtocol(mediaUrl) === 'file:') {
        requestImageFailedRetryDialog(mediaId);
      } else if (mediaType === MEDIA_TYPE_IMAGE && isMediaSelected) {
        requestImageFullscreenPreview(mediaUrl);
      } else if (mediaType === MEDIA_TYPE_IMAGE) {
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
          iconStyle = mediaType === MEDIA_TYPE_IMAGE ? styles.iconRetry : getStylesFromColorScheme(styles.iconRetryVideo, styles.iconRetryVideoDark);
          return createElement(Icon, _extends({
            icon: SvgIconRetry
          }, iconStyle));

        case ICON_TYPE.PLACEHOLDER:
          iconStyle = getStylesFromColorScheme(styles.iconPlaceholder, styles.iconPlaceholderDark);
          break;
      }

      return createElement(Icon, _extends({
        icon: icon
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
      var focalPointValues = !focalPoint ? IMAGE_DEFAULT_FOCAL_POINT : focalPoint;
      return createElement(View, {
        style: [imageFill && styles.imageWithFocalPoint, imageFill && shouldStack && {
          height: styles.imageFill.height
        }]
      }, createElement(TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onMediaPressed,
        onLongPress: openMediaOptions,
        disabled: !isSelected
      }, createElement(View, {
        style: [imageFill && styles.imageCropped, styles.mediaImageContainer, !isUploadInProgress && aligmentStyles]
      }, createElement(Image, {
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
      var showVideo = isURL(mediaUrl) && !isUploadInProgress && !isUploadFailed;
      var videoPlaceholderStyles = getStylesFromColorScheme(styles.videoPlaceholder, styles.videoPlaceholderDark);
      var retryVideoTextStyles = [styles.uploadFailedText, getStylesFromColorScheme(styles.uploadFailedTextVideo, styles.uploadFailedTextVideoDark)];
      return createElement(View, {
        style: styles.mediaVideo
      }, createElement(TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onMediaPressed,
        onLongPress: openMediaOptions,
        disabled: !isSelected
      }, createElement(View, {
        style: [styles.videoContainer, aligmentStyles]
      }, createElement(View, {
        style: [styles.videoContent, {
          aspectRatio: VIDEO_ASPECT_RATIO
        }]
      }, showVideo && createElement(View, {
        style: styles.videoPlayer
      }, createElement(VideoPlayer, {
        isSelected: isSelected,
        style: styles.video,
        source: {
          uri: mediaUrl
        },
        paused: true
      })), !showVideo && createElement(View, {
        style: videoPlaceholderStyles
      }, createElement(View, {
        style: styles.modalIcon
      }, isUploadFailed ? this.getIcon(ICON_TYPE.RETRY) : this.getIcon(ICON_TYPE.PLACEHOLDER)), isUploadFailed && createElement(Text, {
        style: retryVideoTextStyles
      }, retryMessage))))));
    }
  }, {
    key: "renderContent",
    value: function renderContent(params, openMediaOptions) {
      var mediaType = this.props.mediaType;
      var mediaElement = null;

      switch (mediaType) {
        case MEDIA_TYPE_IMAGE:
          mediaElement = this.renderImage(params, openMediaOptions);
          break;

        case MEDIA_TYPE_VIDEO:
          mediaElement = this.renderVideo(params, openMediaOptions);
          break;
      }

      return mediaElement;
    }
  }, {
    key: "renderPlaceholder",
    value: function renderPlaceholder() {
      return createElement(MediaPlaceholder, {
        icon: this.getIcon(ICON_TYPE.PLACEHOLDER),
        labels: {
          title: __('Media area')
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
      var coverUrl = mediaType === MEDIA_TYPE_IMAGE ? mediaUrl : null;

      if (mediaUrl) {
        return createElement(MediaUpload, {
          isReplacingMedia: true,
          onSelect: this.onSelectMediaUploadOption,
          allowedTypes: ALLOWED_MEDIA_TYPES,
          value: mediaId,
          render: function render(_ref) {
            var open = _ref.open,
                getMediaOptions = _ref.getMediaOptions;
            onSetOpenPickerRef(open);
            return createElement(Fragment, null, getMediaOptions(), createElement(MediaUploadProgress, {
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
}(Component);

export default compose(withNotices, withPreferredColorScheme)(MediaContainer);
//# sourceMappingURL=media-container.native.js.map