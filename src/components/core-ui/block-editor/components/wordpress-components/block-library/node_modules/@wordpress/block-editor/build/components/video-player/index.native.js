"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.VIDEO_ASPECT_RATIO = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _reactNative = require("react-native");

var _reactNativeVideo = _interopRequireDefault(require("react-native-video"));

var _styles = _interopRequireDefault(require("./styles.scss"));

var _gridiconPlay = _interopRequireDefault(require("./gridicon-play"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

// Default Video ratio 16:9
var VIDEO_ASPECT_RATIO = 16 / 9;
exports.VIDEO_ASPECT_RATIO = VIDEO_ASPECT_RATIO;

var Video = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(Video, _Component);

  var _super = _createSuper(Video);

  function Video() {
    var _this;

    (0, _classCallCheck2.default)(this, Video);
    _this = _super.apply(this, arguments);
    _this.isIOS = _reactNative.Platform.OS === 'ios';
    _this.state = {
      isFullScreen: false,
      videoContainerHeight: 0
    };
    _this.onPressPlay = _this.onPressPlay.bind((0, _assertThisInitialized2.default)(_this));
    _this.onVideoLayout = _this.onVideoLayout.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(Video, [{
    key: "onVideoLayout",
    value: function onVideoLayout(event) {
      var height = event.nativeEvent.layout.height;

      if (height !== this.state.videoContainerHeight) {
        this.setState({
          videoContainerHeight: height
        });
      }
    }
  }, {
    key: "onPressPlay",
    value: function onPressPlay() {
      if (this.isIOS) {
        if (this.player) {
          this.player.presentFullscreenPlayer();
        }
      } else {
        var source = this.props.source;

        if (source && source.uri) {
          this.openURL(source.uri);
        }
      }
    } // Tries opening the URL outside of the app

  }, {
    key: "openURL",
    value: function openURL(url) {
      _reactNative.Linking.canOpenURL(url).then(function (supported) {
        if (!supported) {
          _reactNative.Alert.alert((0, _i18n.__)('Problem opening the video'), (0, _i18n.__)('No application can handle this request. Please install a Web browser.'));

          window.console.warn('No application found that can open the video with URL: ' + url);
        } else {
          return _reactNative.Linking.openURL(url);
        }
      }).catch(function (err) {
        _reactNative.Alert.alert((0, _i18n.__)('Problem opening the video'), (0, _i18n.__)('An unknown error occurred. Please try again.'));

        window.console.error('An error occurred while opening the video URL: ' + url, err);
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          isSelected = _this$props.isSelected,
          style = _this$props.style;
      var _this$state = this.state,
          isFullScreen = _this$state.isFullScreen,
          videoContainerHeight = _this$state.videoContainerHeight;
      var showPlayButton = videoContainerHeight > 0;
      return (0, _element.createElement)(_reactNative.View, {
        style: _styles.default.videoContainer
      }, (0, _element.createElement)(_reactNativeVideo.default, (0, _extends2.default)({}, this.props, {
        ref: function ref(_ref) {
          _this2.player = _ref;
        } // Using built-in player controls is messing up the layout on iOS.
        // So we are setting controls=false and adding a play button that
        // will trigger presentFullscreenPlayer()
        ,
        controls: false,
        ignoreSilentSwitch: 'ignore',
        paused: !isFullScreen,
        onLayout: this.onVideoLayout,
        onFullscreenPlayerWillPresent: function onFullscreenPlayerWillPresent() {
          _this2.setState({
            isFullScreen: true
          });
        },
        onFullscreenPlayerDidDismiss: function onFullscreenPlayerDidDismiss() {
          _this2.setState({
            isFullScreen: false
          });
        }
      })), showPlayButton && // If we add the play icon as a subview to VideoPlayer then react-native-video decides to show control buttons
      // even if we set controls={ false }, so we are adding our play button as a sibling overlay view.
      (0, _element.createElement)(_reactNative.TouchableOpacity, {
        disabled: !isSelected,
        onPress: this.onPressPlay,
        style: [style, _styles.default.overlayContainer]
      }, (0, _element.createElement)(_reactNative.View, {
        style: _styles.default.blackOverlay
      }), (0, _element.createElement)(_components.Icon, {
        icon: _gridiconPlay.default,
        style: _styles.default.playIcon,
        size: _styles.default.playIcon.size
      })));
    }
  }]);
  return Video;
}(_element.Component);

var _default = Video;
exports.default = _default;
//# sourceMappingURL=index.native.js.map