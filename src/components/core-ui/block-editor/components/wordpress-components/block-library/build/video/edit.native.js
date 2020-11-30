"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _react = _interopRequireDefault(require("react"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _url = require("@wordpress/url");

var _hooks = require("@wordpress/hooks");

var _icons = require("@wordpress/icons");

var _style = _interopRequireDefault(require("./style.scss"));

var _iconRetry = _interopRequireDefault(require("./icon-retry"));

var _editCommonSettings = _interopRequireDefault(require("./edit-common-settings"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var ICON_TYPE = {
  PLACEHOLDER: 'placeholder',
  RETRY: 'retry',
  UPLOAD: 'upload'
};

var VideoEdit = /*#__PURE__*/function (_React$Component) {
  (0, _inherits2.default)(VideoEdit, _React$Component);

  var _super = _createSuper(VideoEdit);

  function VideoEdit(props) {
    var _this;

    (0, _classCallCheck2.default)(this, VideoEdit);
    _this = _super.call(this, props);
    _this.state = {
      isCaptionSelected: false,
      videoContainerHeight: 0
    };
    _this.mediaUploadStateReset = _this.mediaUploadStateReset.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSelectMediaUploadOption = _this.onSelectMediaUploadOption.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind((0, _assertThisInitialized2.default)(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind((0, _assertThisInitialized2.default)(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind((0, _assertThisInitialized2.default)(_this));
    _this.onVideoPressed = _this.onVideoPressed.bind((0, _assertThisInitialized2.default)(_this));
    _this.onVideoContanerLayout = _this.onVideoContanerLayout.bind((0, _assertThisInitialized2.default)(_this));
    _this.onFocusCaption = _this.onFocusCaption.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(VideoEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var attributes = this.props.attributes;

      if (attributes.id && (0, _url.getProtocol)(attributes.src) === 'file:') {
        (0, _reactNativeBridge.mediaUploadSync)();
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      // this action will only exist if the user pressed the trash button on the block holder
      if ((0, _hooks.hasAction)('blocks.onRemoveBlockCheckUpload') && this.state.isUploadInProgress) {
        (0, _hooks.doAction)('blocks.onRemoveBlockCheckUpload', this.props.attributes.id);
      }
    }
  }, {
    key: "onVideoPressed",
    value: function onVideoPressed() {
      var attributes = this.props.attributes;

      if (this.state.isUploadInProgress) {
        (0, _reactNativeBridge.requestImageUploadCancelDialog)(attributes.id);
      } else if (attributes.id && (0, _url.getProtocol)(attributes.src) === 'file:') {
        (0, _reactNativeBridge.requestImageFailedRetryDialog)(attributes.id);
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
      var height = width / _blockEditor.VIDEO_ASPECT_RATIO;

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
          return (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
            icon: _iconRetry.default
          }, _style.default.icon));

        case ICON_TYPE.PLACEHOLDER:
          iconStyle = this.props.getStylesFromColorScheme(_style.default.icon, _style.default.iconDark);
          break;

        case ICON_TYPE.UPLOAD:
          iconStyle = this.props.getStylesFromColorScheme(_style.default.iconUploading, _style.default.iconUploadingDark);
          break;
      }

      return (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
        icon: _icons.video
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
      var toolbarEditButton = (0, _element.createElement)(_blockEditor.MediaUpload, {
        allowedTypes: [_blockEditor.MEDIA_TYPE_VIDEO],
        isReplacingMedia: true,
        onSelect: this.onSelectMediaUploadOption,
        render: function render(_ref2) {
          var open = _ref2.open,
              getMediaOptions = _ref2.getMediaOptions;
          return (0, _element.createElement)(_components.ToolbarGroup, null, getMediaOptions(), (0, _element.createElement)(_components.ToolbarButton, {
            label: (0, _i18n.__)('Edit video'),
            icon: _icons.replace,
            onClick: open
          }));
        }
      });

      if (!id) {
        return (0, _element.createElement)(_reactNative.View, {
          style: {
            flex: 1
          }
        }, (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
          allowedTypes: [_blockEditor.MEDIA_TYPE_VIDEO],
          onSelect: this.onSelectMediaUploadOption,
          icon: this.getIcon(ICON_TYPE.PLACEHOLDER),
          onFocus: this.props.onFocus
        }));
      }

      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        accessible: !isSelected,
        onPress: this.onVideoPressed,
        disabled: !isSelected
      }, (0, _element.createElement)(_reactNative.View, {
        style: {
          flex: 1
        }
      }, !this.state.isCaptionSelected && (0, _element.createElement)(_blockEditor.BlockControls, null, toolbarEditButton), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Video settings')
      }, (0, _element.createElement)(_editCommonSettings.default, {
        setAttributes: setAttributes,
        attributes: attributes
      }))), (0, _element.createElement)(_blockEditor.MediaUploadProgress, {
        mediaId: id,
        onFinishMediaUploadWithSuccess: this.finishMediaUploadWithSuccess,
        onFinishMediaUploadWithFailure: this.finishMediaUploadWithFailure,
        onUpdateMediaProgress: this.updateMediaProgress,
        onMediaUploadStateReset: this.mediaUploadStateReset,
        renderContent: function renderContent(_ref3) {
          var isUploadInProgress = _ref3.isUploadInProgress,
              isUploadFailed = _ref3.isUploadFailed,
              retryMessage = _ref3.retryMessage;
          var showVideo = (0, _url.isURL)(src) && !isUploadInProgress && !isUploadFailed;

          var icon = _this2.getIcon(isUploadFailed ? ICON_TYPE.RETRY : ICON_TYPE.UPLOAD);

          var styleIconContainer = isUploadFailed ? _style.default.modalIconRetry : _style.default.modalIcon;
          var iconContainer = (0, _element.createElement)(_reactNative.View, {
            style: styleIconContainer
          }, icon);

          var videoStyle = _objectSpread({
            height: videoContainerHeight
          }, _style.default.video);

          var containerStyle = showVideo && isSelected ? _style.default.containerFocused : _style.default.container;
          return (0, _element.createElement)(_reactNative.View, {
            onLayout: _this2.onVideoContanerLayout,
            style: containerStyle
          }, showVideo && (0, _element.createElement)(_reactNative.View, {
            style: _style.default.videoContainer
          }, (0, _element.createElement)(_blockEditor.VideoPlayer, {
            isSelected: isSelected && !_this2.state.isCaptionSelected,
            style: videoStyle,
            source: {
              uri: src
            },
            paused: true
          })), !showVideo && (0, _element.createElement)(_reactNative.View, {
            style: _objectSpread({
              height: videoContainerHeight,
              width: '100%'
            }, _this2.props.getStylesFromColorScheme(_style.default.placeholderContainer, _style.default.placeholderContainerDark))
          }, videoContainerHeight > 0 && iconContainer, isUploadFailed && (0, _element.createElement)(_reactNative.Text, {
            style: _style.default.uploadFailedText
          }, retryMessage)));
        }
      }), (0, _element.createElement)(_blockEditor.BlockCaption, {
        accessible: true,
        accessibilityLabelCreator: function accessibilityLabelCreator(caption) {
          return (0, _lodash.isEmpty)(caption) ?
          /* translators: accessibility text. Empty video caption. */
          (0, _i18n.__)('Video caption. Empty') : (0, _i18n.sprintf)(
          /* translators: accessibility text. %s: video caption. */
          (0, _i18n.__)('Video caption. %s'), caption);
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
}(_react.default.Component);

var _default = (0, _compose.withPreferredColorScheme)(VideoEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map