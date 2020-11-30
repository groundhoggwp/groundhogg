"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.MediaUploadProgress = exports.MEDIA_UPLOAD_STATE_RESET = exports.MEDIA_UPLOAD_STATE_FAILED = exports.MEDIA_UPLOAD_STATE_SUCCEEDED = exports.MEDIA_UPLOAD_STATE_UPLOADING = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _react = _interopRequireDefault(require("react"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _styles = _interopRequireDefault(require("./styles.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MEDIA_UPLOAD_STATE_UPLOADING = 1;
exports.MEDIA_UPLOAD_STATE_UPLOADING = MEDIA_UPLOAD_STATE_UPLOADING;
var MEDIA_UPLOAD_STATE_SUCCEEDED = 2;
exports.MEDIA_UPLOAD_STATE_SUCCEEDED = MEDIA_UPLOAD_STATE_SUCCEEDED;
var MEDIA_UPLOAD_STATE_FAILED = 3;
exports.MEDIA_UPLOAD_STATE_FAILED = MEDIA_UPLOAD_STATE_FAILED;
var MEDIA_UPLOAD_STATE_RESET = 4;
exports.MEDIA_UPLOAD_STATE_RESET = MEDIA_UPLOAD_STATE_RESET;

var MediaUploadProgress = /*#__PURE__*/function (_React$Component) {
  (0, _inherits2.default)(MediaUploadProgress, _React$Component);

  var _super = _createSuper(MediaUploadProgress);

  function MediaUploadProgress(props) {
    var _this;

    (0, _classCallCheck2.default)(this, MediaUploadProgress);
    _this = _super.call(this, props);
    _this.state = {
      progress: 0,
      isUploadInProgress: false,
      isUploadFailed: false
    };
    _this.mediaUpload = _this.mediaUpload.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(MediaUploadProgress, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.addMediaUploadListener();
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.removeMediaUploadListener();
    }
  }, {
    key: "mediaUpload",
    value: function mediaUpload(payload) {
      var mediaId = this.props.mediaId;

      if (payload.mediaId !== mediaId) {
        return;
      }

      switch (payload.state) {
        case MEDIA_UPLOAD_STATE_UPLOADING:
          this.updateMediaProgress(payload);
          break;

        case MEDIA_UPLOAD_STATE_SUCCEEDED:
          this.finishMediaUploadWithSuccess(payload);
          break;

        case MEDIA_UPLOAD_STATE_FAILED:
          this.finishMediaUploadWithFailure(payload);
          break;

        case MEDIA_UPLOAD_STATE_RESET:
          this.mediaUploadStateReset(payload);
          break;
      }
    }
  }, {
    key: "updateMediaProgress",
    value: function updateMediaProgress(payload) {
      this.setState({
        progress: payload.progress,
        isUploadInProgress: true,
        isUploadFailed: false
      });

      if (this.props.onUpdateMediaProgress) {
        this.props.onUpdateMediaProgress(payload);
      }
    }
  }, {
    key: "finishMediaUploadWithSuccess",
    value: function finishMediaUploadWithSuccess(payload) {
      this.setState({
        isUploadInProgress: false
      });

      if (this.props.onFinishMediaUploadWithSuccess) {
        this.props.onFinishMediaUploadWithSuccess(payload);
      }
    }
  }, {
    key: "finishMediaUploadWithFailure",
    value: function finishMediaUploadWithFailure(payload) {
      this.setState({
        isUploadInProgress: false,
        isUploadFailed: true
      });

      if (this.props.onFinishMediaUploadWithFailure) {
        this.props.onFinishMediaUploadWithFailure(payload);
      }
    }
  }, {
    key: "mediaUploadStateReset",
    value: function mediaUploadStateReset(payload) {
      this.setState({
        isUploadInProgress: false,
        isUploadFailed: false
      });

      if (this.props.onMediaUploadStateReset) {
        this.props.onMediaUploadStateReset(payload);
      }
    }
  }, {
    key: "addMediaUploadListener",
    value: function addMediaUploadListener() {
      var _this2 = this;

      //if we already have a subscription not worth doing it again
      if (this.subscriptionParentMediaUpload) {
        return;
      }

      this.subscriptionParentMediaUpload = (0, _reactNativeBridge.subscribeMediaUpload)(function (payload) {
        _this2.mediaUpload(payload);
      });
    }
  }, {
    key: "removeMediaUploadListener",
    value: function removeMediaUploadListener() {
      if (this.subscriptionParentMediaUpload) {
        this.subscriptionParentMediaUpload.remove();
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props$renderCon = this.props.renderContent,
          renderContent = _this$props$renderCon === void 0 ? function () {
        return null;
      } : _this$props$renderCon;
      var _this$state = this.state,
          isUploadInProgress = _this$state.isUploadInProgress,
          isUploadFailed = _this$state.isUploadFailed;
      var showSpinner = this.state.isUploadInProgress;
      var progress = this.state.progress * 100; // eslint-disable-next-line @wordpress/i18n-no-collapsible-whitespace

      var retryMessage = (0, _i18n.__)('Failed to insert media.\nPlease tap for options.');
      return (0, _element.createElement)(_reactNative.View, {
        style: _styles.default.mediaUploadProgress,
        pointerEvents: "box-none"
      }, showSpinner && (0, _element.createElement)(_reactNative.View, {
        style: _styles.default.progressBar
      }, (0, _element.createElement)(_components.Spinner, {
        progress: progress
      })), renderContent({
        isUploadInProgress: isUploadInProgress,
        isUploadFailed: isUploadFailed,
        retryMessage: retryMessage
      }));
    }
  }]);
  return MediaUploadProgress;
}(_react.default.Component);

exports.MediaUploadProgress = MediaUploadProgress;
var _default = MediaUploadProgress;
exports.default = _default;
//# sourceMappingURL=index.native.js.map