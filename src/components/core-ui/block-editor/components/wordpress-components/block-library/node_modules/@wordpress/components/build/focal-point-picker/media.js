"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Media;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _focalPointPickerStyle = require("./styles/focal-point-picker-style");

var _utils = require("./utils");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Media(_ref) {
  var alt = _ref.alt,
      autoPlay = _ref.autoPlay,
      src = _ref.src,
      _ref$onLoad = _ref.onLoad,
      onLoad = _ref$onLoad === void 0 ? _lodash.noop : _ref$onLoad,
      mediaRef = _ref.mediaRef,
      _ref$muted = _ref.muted,
      muted = _ref$muted === void 0 ? true : _ref$muted,
      props = (0, _objectWithoutProperties2.default)(_ref, ["alt", "autoPlay", "src", "onLoad", "mediaRef", "muted"]);

  if (!src) {
    return (0, _element.createElement)(MediaPlaceholderElement, {
      className: "components-focal-point-picker__media components-focal-point-picker__media--placeholder",
      onLoad: onLoad,
      mediaRef: mediaRef
    });
  }

  var isVideo = (0, _utils.isVideoType)(src);
  return isVideo ? (0, _element.createElement)("video", (0, _extends2.default)({}, props, {
    autoPlay: autoPlay,
    className: "components-focal-point-picker__media components-focal-point-picker__media--video",
    loop: true,
    muted: muted,
    onLoadedData: onLoad,
    ref: mediaRef,
    src: src
  })) : (0, _element.createElement)("img", (0, _extends2.default)({}, props, {
    alt: alt,
    className: "components-focal-point-picker__media components-focal-point-picker__media--image",
    onLoad: onLoad,
    ref: mediaRef,
    src: src
  }));
}

function MediaPlaceholderElement(_ref2) {
  var mediaRef = _ref2.mediaRef,
      _ref2$onLoad = _ref2.onLoad,
      onLoad = _ref2$onLoad === void 0 ? _lodash.noop : _ref2$onLoad,
      props = (0, _objectWithoutProperties2.default)(_ref2, ["mediaRef", "onLoad"]);
  var onLoadRef = (0, _element.useRef)(onLoad);
  /**
   * This async callback mimics the onLoad (img) / onLoadedData (video) callback
   * for media elements. It is used in the main <FocalPointPicker /> component
   * to calculate the dimensions + boundaries for positioning.
   */

  (0, _element.useLayoutEffect)(function () {
    window.requestAnimationFrame(function () {
      onLoadRef.current();
    });
  }, []);
  return (0, _element.createElement)(_focalPointPickerStyle.MediaPlaceholder, (0, _extends2.default)({
    ref: mediaRef
  }, props));
}
//# sourceMappingURL=media.js.map