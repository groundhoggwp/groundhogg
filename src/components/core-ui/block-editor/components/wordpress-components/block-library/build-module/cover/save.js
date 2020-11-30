import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { InnerBlocks, getColorClassName, __experimentalGetGradientClass } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { IMAGE_BACKGROUND_TYPE, VIDEO_BACKGROUND_TYPE, backgroundImageStyles, dimRatioToClass, isContentPositionCenter, getPositionClassName } from './shared';
export default function save(_ref) {
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
  var overlayColorClass = getColorClassName('background-color', overlayColor);

  var gradientClass = __experimentalGetGradientClass(gradient);

  var minHeight = minHeightUnit ? "".concat(minHeightProp).concat(minHeightUnit) : minHeightProp;
  var isImageBackground = IMAGE_BACKGROUND_TYPE === backgroundType;
  var isVideoBackground = VIDEO_BACKGROUND_TYPE === backgroundType;
  var style = isImageBackground ? backgroundImageStyles(url) : {};
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

  var classes = classnames(dimRatioToClass(dimRatio), overlayColorClass, (_classnames = {
    'has-background-dim': dimRatio !== 0,
    'has-parallax': hasParallax,
    'has-background-gradient': gradient || customGradient
  }, _defineProperty(_classnames, gradientClass, !url && gradientClass), _defineProperty(_classnames, 'has-custom-content-position', !isContentPositionCenter(contentPosition)), _classnames), getPositionClassName(contentPosition));
  return createElement("div", {
    className: classes,
    style: style
  }, url && (gradient || customGradient) && dimRatio !== 0 && createElement("span", {
    "aria-hidden": "true",
    className: classnames('wp-block-cover__gradient-background', gradientClass),
    style: customGradient ? {
      background: customGradient
    } : undefined
  }), isVideoBackground && url && createElement("video", {
    className: "wp-block-cover__video-background",
    autoPlay: true,
    muted: true,
    loop: true,
    playsInline: true,
    src: url,
    style: videoStyle
  }), createElement("div", {
    className: "wp-block-cover__inner-container"
  }, createElement(InnerBlocks.Content, null)));
}
//# sourceMappingURL=save.js.map