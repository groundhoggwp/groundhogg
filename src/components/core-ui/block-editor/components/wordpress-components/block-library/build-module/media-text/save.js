import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { noop, isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { InnerBlocks } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { imageFillStyles } from './media-container';
import { DEFAULT_MEDIA_SIZE_SLUG } from './constants';
var DEFAULT_MEDIA_WIDTH = 50;
export default function save(_ref) {
  var _classnames, _classnames2;

  var attributes = _ref.attributes;
  var isStackedOnMobile = attributes.isStackedOnMobile,
      mediaAlt = attributes.mediaAlt,
      mediaPosition = attributes.mediaPosition,
      mediaType = attributes.mediaType,
      mediaUrl = attributes.mediaUrl,
      mediaWidth = attributes.mediaWidth,
      mediaId = attributes.mediaId,
      verticalAlignment = attributes.verticalAlignment,
      imageFill = attributes.imageFill,
      focalPoint = attributes.focalPoint,
      linkClass = attributes.linkClass,
      href = attributes.href,
      linkTarget = attributes.linkTarget,
      rel = attributes.rel;
  var mediaSizeSlug = attributes.mediaSizeSlug || DEFAULT_MEDIA_SIZE_SLUG;
  var newRel = isEmpty(rel) ? undefined : rel;
  var imageClasses = classnames((_classnames = {}, _defineProperty(_classnames, "wp-image-".concat(mediaId), mediaId && mediaType === 'image'), _defineProperty(_classnames, "size-".concat(mediaSizeSlug), mediaId && mediaType === 'image'), _classnames));

  var _image = createElement("img", {
    src: mediaUrl,
    alt: mediaAlt,
    className: imageClasses || null
  });

  if (href) {
    _image = createElement("a", {
      className: linkClass,
      href: href,
      target: linkTarget,
      rel: newRel
    }, _image);
  }

  var mediaTypeRenders = {
    image: function image() {
      return _image;
    },
    video: function video() {
      return createElement("video", {
        controls: true,
        src: mediaUrl
      });
    }
  };
  var className = classnames((_classnames2 = {
    'has-media-on-the-right': 'right' === mediaPosition,
    'is-stacked-on-mobile': isStackedOnMobile
  }, _defineProperty(_classnames2, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), _defineProperty(_classnames2, 'is-image-fill', imageFill), _classnames2));
  var backgroundStyles = imageFill ? imageFillStyles(mediaUrl, focalPoint) : {};
  var gridTemplateColumns;

  if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
    gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
  }

  var style = {
    gridTemplateColumns: gridTemplateColumns
  };
  return createElement("div", {
    className: className,
    style: style
  }, createElement("figure", {
    className: "wp-block-media-text__media",
    style: backgroundStyles
  }, (mediaTypeRenders[mediaType] || noop)()), createElement("div", {
    className: "wp-block-media-text__content"
  }, createElement(InnerBlocks.Content, null)));
}
//# sourceMappingURL=save.js.map