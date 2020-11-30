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
 * External dependencies
 */
import React from 'react';
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { subscribeMediaUpload } from '@wordpress/react-native-bridge';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
export var MEDIA_UPLOAD_STATE_UPLOADING = 1;
export var MEDIA_UPLOAD_STATE_SUCCEEDED = 2;
export var MEDIA_UPLOAD_STATE_FAILED = 3;
export var MEDIA_UPLOAD_STATE_RESET = 4;
export var MediaUploadProgress = /*#__PURE__*/function (_React$Component) {
  _inherits(MediaUploadProgress, _React$Component);

  var _super = _createSuper(MediaUploadProgress);

  function MediaUploadProgress(props) {
    var _this;

    _classCallCheck(this, MediaUploadProgress);

    _this = _super.call(this, props);
    _this.state = {
      progress: 0,
      isUploadInProgress: false,
      isUploadFailed: false
    };
    _this.mediaUpload = _this.mediaUpload.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(MediaUploadProgress, [{
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

      this.subscriptionParentMediaUpload = subscribeMediaUpload(function (payload) {
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

      var retryMessage = __('Failed to insert media.\nPlease tap for options.');

      return createElement(View, {
        style: styles.mediaUploadProgress,
        pointerEvents: "box-none"
      }, showSpinner && createElement(View, {
        style: styles.progressBar
      }, createElement(Spinner, {
        progress: progress
      })), renderContent({
        isUploadInProgress: isUploadInProgress,
        isUploadFailed: isUploadFailed,
        retryMessage: retryMessage
      }));
    }
  }]);

  return MediaUploadProgress;
}(React.Component);
export default MediaUploadProgress;
//# sourceMappingURL=index.native.js.map