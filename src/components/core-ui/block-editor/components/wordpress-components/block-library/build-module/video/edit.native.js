import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _extends from "@babel/runtime/helpers/esm/extends";
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
import React from 'react';
import { View, TouchableWithoutFeedback, Text } from 'react-native';
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { mediaUploadSync, requestImageFailedRetryDialog, requestImageUploadCancelDialog } from '@wordpress/react-native-bridge';
import { Icon, ToolbarButton, ToolbarGroup, PanelBody } from '@wordpress/components';
import { withPreferredColorScheme } from '@wordpress/compose';
import { BlockCaption, MediaPlaceholder, MediaUpload, MediaUploadProgress, MEDIA_TYPE_VIDEO, BlockControls, VIDEO_ASPECT_RATIO, VideoPlayer, InspectorControls } from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';
import { isURL, getProtocol } from '@wordpress/url';
import { doAction, hasAction } from '@wordpress/hooks';
import { video as SvgIcon, replace } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import style from './style.scss';
import SvgIconRetry from './icon-retry';
import VideoCommonSettings from './edit-common-settings';
var ICON_TYPE = {
  PLACEHOLDER: 'placeholder',
  RETRY: 'retry',
  UPLOAD: 'upload'
};

var VideoEdit = /*#__PURE__*/function (_React$Component) {
  _inherits(VideoEdit, _React$Component);

  var _super = _createSuper(VideoEdit);

  function VideoEdit(props) {
    var _this;

    _classCallCheck(this, VideoEdit);

    _this = _super.call(this, props);
    _this.state = {
      isCaptionSelected: false,
      videoContainerHeight: 0
    };
    _this.mediaUploadStateReset = _this.mediaUploadStateReset.bind(_assertThisInitialized(_this));
    _this.onSelectMediaUploadOption = _this.onSelectMediaUploadOption.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind(_assertThisInitialized(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind(_assertThisInitialized(_this));
    _this.onVideoPressed = _this.onVideoPressed.bind(_assertThisInitialized(_this));
    _this.onVideoContanerLayout = _this.onVideoContanerLayout.bind(_assertThisInitialized(_this));
    _this.onFocusCaption = _this.onFocusCaption.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(VideoEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var attributes = this.props.attributes;

      if (attributes.id && getProtocol(attributes.src) === 'file:') {
        mediaUploadSync();
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      // this action will only exist if the user pressed the trash button on the block holder
      if (hasAction('blocks.onRemoveBlockCheckUpload') && this.state.isUploadInProgress) {
        doAction('blocks.onRemoveBlockCheckUpload', this.props.attributes.id);
      }
    }
  }, {
    key: "onVideoPressed",
    value: function onVideoPressed() {
      var attributes = this.props.attributes;

      if (this.state.isUploadInProgress) {
        requestImageUploadCancelDialog(attributes.id);
      } else if (attributes.id && getProtocol(attributes.src) === 'file:') {
        requestImageFailedRetryDialog(attributes.id);
      }

      this.setState({
        isCaptionSelected: false
      });
    }
  }, {
    key: "onFocusCaption",
    value: function onFocusCaption() {
      if (!this.state.isCaptionSelected) {
        this.setState({
          isCaptionSelected: true
        });
      }
    }
  }, {
    key: "updateMediaProgress",
    value: function updateMediaProgress(payload) {
      var setAttributes = this.props.setAttributes;

      if (payload.mediaUrl) {
        setAttributes({
          url: payload.mediaUrl
        });
      }

      if (!this.state.isUploadInProgress) {
        this.setState({
          isUploadInProgress: true
        });
      }
    }
  }, {
    key: "finishMediaUploadWithSuccess",
    value: function finishMediaUploadWithSuccess(payload) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        src: payload.mediaUrl,
        id: payload.mediaServerId
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "finishMediaUploadWithFailure",
    value: function finishMediaUploadWithFailure(payload) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        id: payload.mediaId
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "mediaUploadStateReset",
    value: function mediaUploadStateReset() {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        id: null,
        src: null
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "onSelectMediaUploadOption",
    value: function onSelectMediaUploadOption(_ref) {
      var id = _ref.id,
          url = _ref.url;
      var setAttributes = this.props.setAttributes;
      setAttributes({
        id: id,
        src: url
      });
    }
  }, {
    key: "onVideoContanerLayout",
    value: function onVideoContanerLayout(event) {
      var width = event.nativeEvent.layout.width;
      var height = width / VIDEO_ASPECT_RATIO;

      if (height !== this.state.videoContainerHeight) {
        this.setState({
          videoContainerHeight: height
        });
      }
    }
  }, {
    key: "getIcon",
    value: function getIcon(iconType) {
      var iconStyle;

      switch (iconType) {
        case ICON_TYPE.RETRY:
          return createElement(Icon, _extends({
            icon: SvgIconRetry
          }, style.icon));

        case ICON_TYPE.PLACEHOLDER:
          iconStyle = this.props.getStylesFromColorScheme(style.icon, style.iconDark);
          break;

        case ICON_TYPE.UPLOAD:
          iconStyle = this.props.getStylesFromColorScheme(style.iconUploading, style.iconUploadingDark);
          break;
      }

      return createElement(Icon, _extends({
        icon: SvgIcon
      }, iconStyle));
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          setAttributes = _this$props.setAttributes,
          attributes = _this$props.attributes,
          isSelected = _this$props.isSelected;
      var id = attributes.id,
          src = attributes.src;
      var videoContainerHeight = this.state.videoContainerHeight;
      var toolbarEditButton = createElement(MediaUpload, {
        allowedTypes: [MEDIA_TYPE_VIDEO],
        isReplacingMedia: true,
        onSelect: this.onSelectMediaUploadOption,
        render: function render(_ref2) {
          var open = _ref2.open,
              getMediaOptions = _ref2.getMediaOptions;
          return createElement(ToolbarGroup, null, getMediaOptions(), createElement(ToolbarButton, {
            label: __('Edit video'),
            icon: replace,
            onClick: open
          }));
        }
      });

      if (!id) {
        return createElement(View, {
          style: {
            flex: 1
          }
        }, createElement(MediaPlaceholder, {
          allowedTypes: [MEDIA_TYPE_VIDEO],
          onSelect: this.onSelectMediaUploadOption,
          icon: this.getIcon(ICON_TYPE.PLACEHOLDER),
          onFocus: this.props.onFocus
        }));
      }

      return createElement(TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onVideoPressed,
        disabled: !isSelected
      }, createElement(View, {
        style: {
          flex: 1
        }
      }, !this.state.isCaptionSelected && createElement(BlockControls, null, toolbarEditButton), createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Video settings')
      }, createElement(VideoCommonSettings, {
        setAttributes: setAttributes,
        attributes: attributes
      }))), createElement(MediaUploadProgress, {
        mediaId: id,
        onFinishMediaUploadWithSuccess: this.finishMediaUploadWithSuccess,
        onFinishMediaUploadWithFailure: this.finishMediaUploadWithFailure,
        onUpdateMediaProgress: this.updateMediaProgress,
        onMediaUploadStateReset: this.mediaUploadStateReset,
        renderContent: function renderContent(_ref3) {
          var isUploadInProgress = _ref3.isUploadInProgress,
              isUploadFailed = _ref3.isUploadFailed,
              retryMessage = _ref3.retryMessage;
          var showVideo = isURL(src) && !isUploadInProgress && !isUploadFailed;

          var icon = _this2.getIcon(isUploadFailed ? ICON_TYPE.RETRY : ICON_TYPE.UPLOAD);

          var styleIconContainer = isUploadFailed ? style.modalIconRetry : style.modalIcon;
          var iconContainer = createElement(View, {
            style: styleIconContainer
          }, icon);

          var videoStyle = _objectSpread({
            height: videoContainerHeight
          }, style.video);

          var containerStyle = showVideo && isSelected ? style.containerFocused : style.container;
          return createElement(View, {
            onLayout: _this2.onVideoContanerLayout,
            style: containerStyle
          }, showVideo && createElement(View, {
            style: style.videoContainer
          }, createElement(VideoPlayer, {
            isSelected: isSelected && !_this2.state.isCaptionSelected,
            style: videoStyle,
            source: {
              uri: src
            },
            paused: true
          })), !showVideo && createElement(View, {
            style: _objectSpread({
              height: videoContainerHeight,
              width: '100%'
            }, _this2.props.getStylesFromColorScheme(style.placeholderContainer, style.placeholderContainerDark))
          }, videoContainerHeight > 0 && iconContainer, isUploadFailed && createElement(Text, {
            style: style.uploadFailedText
          }, retryMessage)));
        }
      }), createElement(BlockCaption, {
        accessible: true,
        accessibilityLabelCreator: function accessibilityLabelCreator(caption) {
          return isEmpty(caption) ?
          /* translators: accessibility text. Empty video caption. */
          __('Video caption. Empty') : sprintf(
          /* translators: accessibility text. %s: video caption. */
          __('Video caption. %s'), caption);
        },
        clientId: this.props.clientId,
        isSelected: this.state.isCaptionSelected,
        onFocus: this.onFocusCaption,
        onBlur: this.props.onBlur // always assign onBlur as props
        ,
        insertBlocksAfter: this.props.insertBlocksAfter
      })));
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(props, state) {
      // Avoid a UI flicker in the toolbar by insuring that isCaptionSelected
      // is updated immediately any time the isSelected prop becomes false
      return {
        isCaptionSelected: props.isSelected && state.isCaptionSelected
      };
    }
  }]);

  return VideoEdit;
}(React.Component);

export default withPreferredColorScheme(VideoEdit);
//# sourceMappingURL=edit.native.js.map