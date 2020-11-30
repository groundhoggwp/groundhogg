"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _shared = require("./shared");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function save(_ref) {
  var _classnames;

  var attributes = _ref.attributes;
  var backgroundType = attributes.backgroundType,
      gradient = attributes.gradient,
      contentPosition = attributes.contentPosition,
      customGradient = attributes.customGradient,
      customOverlayColor = attributes.customOverlayColor,
      dimRatio = attributes.dimRatio,
      focalPoint = attributes.focalPoint,
      hasParallax = attributes.hasParallax,
      overlayColor = attributes.overlayColor,
      url = attributes.url,
      minHeightProp = attributes.minHeight,
      minHeightUnit = attributes.minHeightUnit;
  var overlayColorClass = (0, _blockEditor.getColorClassName)('background-color', overlayColor);
  var gradientClass = (0, _blockEditor.__experimentalGetGradientClass)(gradient);
  var minHeight = minHeightUnit ? "".concat(minHeightProp).concat(minHeightUnit) : minHeightProp;
  var isImageBackground = _shared.IMAGE_BACKGROUND_TYPE === backgroundType;
  var isVideoBackground = _shared.VIDEO_BACKGROUND_TYPE === backgroundType;
  var style = isImageBackground ? (0, _shared.backgroundImageStyles)(url) : {};
  var videoStyle = {};

  if (!overlayColorClass) {
    style.backgroundColor = customOverlayColor;
  }

  if (customGradient && !url) {
    style.background = customGradient;
  }

  style.minHeight = minHeight || undefined;
  var positionValue;

  if (focalPoint) {
    positionValue = "".concat(Math.round(focalPoint.x * 100), "% ").concat(Math.round(focalPoint.y * 100), "%");

    if (isImageBackground && !hasParallax) {
      style.backgroundPosition = positionValue;
    }

    if (isVideoBackground) {
      videoStyle.objectPosition = positionValue;
    }
  }

  var classes = (0, _classnames2.default)((0, _shared.dimRatioToClass)(dimRatio), overlayColorClass, (_classnames = {
    'has-background-dim': dimRatio !== 0,
    'has-parallax': hasParallax,
    'has-background-gradient': gradient || customGradient
  }, (0, _defineProperty2.default)(_classnames, gradientClass, !url && gradientClass), (0, _defineProperty2.default)(_classnames, 'has-custom-content-position', !(0, _shared.isContentPositionCenter)(contentPosition)), _classnames), (0, _shared.getPositionClassName)(contentPosition));
  return (0, _element.createElement)("div", {
    className: classes,
    style: style
  }, url && (gradient || customGradient) && dimRatio !== 0 && (0, _element.createElement)("span", {
    "aria-hidden": "true",
    className: (0, _classnames2.default)('wp-block-cover__gradient-background', gradientClass),
    style: customGradient ? {
      background: customGradient
    } : undefined
  }), isVideoBackground && url && (0, _element.createElement)("video", {
    className: "wp-block-cover__video-background",
    autoPlay: true,
    muted: true,
    loop: true,
    playsInline: true,
    src: url,
    style: videoStyle
  }), (0, _element.createElement)("div", {
    className: "wp-block-cover__inner-container"
  }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
}
//# sourceMappingURL=save.js.map