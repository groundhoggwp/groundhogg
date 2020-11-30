import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */

import { View, TouchableOpacity, Platform, Linking, Alert } from 'react-native';
import { default as VideoPlayer } from 'react-native-video';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
import PlayIcon from './gridicon-play'; // Default Video ratio 16:9

export var VIDEO_ASPECT_RATIO = 16 / 9;

var Video = /*#__PURE__*/function (_Component) {
  _inherits(Video, _Component);

  var _super = _createSuper(Video);

  function Video() {
    var _this;

    _classCallCheck(this, Video);

    _this = _super.apply(this, arguments);
    _this.isIOS = Platform.OS === 'ios';
    _this.state = {
      isFullScreen: false,
      videoContainerHeight: 0
    };
    _this.onPressPlay = _this.onPressPlay.bind(_assertThisInitialized(_this));
    _this.onVideoLayout = _this.onVideoLayout.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(Video, [{
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
      Linking.canOpenURL(url).then(function (supported) {
        if (!supported) {
          Alert.alert(__('Problem opening the video'), __('No application can handle this request. Please install a Web browser.'));
          window.console.warn('No application found that can open the video with URL: ' + url);
        } else {
          return Linking.openURL(url);
        }
      }).catch(function (err) {
        Alert.alert(__('Problem opening the video'), __('An unknown error occurred. Please try again.'));
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
      return createElement(View, {
        style: styles.videoContainer
      }, createElement(VideoPlayer, _extends({}, this.props, {
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
      createElement(TouchableOpacity, {
        disabled: !isSelected,
        onPress: this.onPressPlay,
        style: [style, styles.overlayContainer]
      }, createElement(View, {
        style: styles.blackOverlay
      }), createElement(Icon, {
        icon: PlayIcon,
        style: styles.playIcon,
        size: styles.playIcon.size
      })));
    }
  }]);

  return Video;
}(Component);

export default Video;
//# sourceMappingURL=index.native.js.map