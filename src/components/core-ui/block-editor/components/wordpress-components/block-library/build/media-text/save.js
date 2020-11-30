"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames3 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

var _mediaContainer = require("./media-container");

var _constants = require("./constants");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var DEFAULT_MEDIA_WIDTH = 50;

function save(_ref) {
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
  var mediaSizeSlug = attributes.mediaSizeSlug || _constants.DEFAULT_MEDIA_SIZE_SLUG;
  var newRel = (0, _lodash.isEmpty)(rel) ? undefined : rel;
  var imageClasses = (0, _classnames3.default)((_classnames = {}, (0, _defineProperty2.default)(_classnames, "wp-image-".concat(mediaId), mediaId && mediaType === 'image'), (0, _defineProperty2.default)(_classnames, "size-".concat(mediaSizeSlug), mediaId && mediaType === 'image'), _classnames));

  var _image = (0, _element.createElement)("img", {
    src: mediaUrl,
    alt: mediaAlt,
    className: imageClasses || null
  });

  if (href) {
    _image = (0, _element.createElement)("a", {
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
      return (0, _element.createElement)("video", {
        controls: true,
        src: mediaUrl
      });
    }
  };
  var className = (0, _classnames3.default)((_classnames2 = {
    'has-media-on-the-right': 'right' === mediaPosition,
    'is-stacked-on-mobile': isStackedOnMobile
  }, (0, _defineProperty2.default)(_classnames2, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), (0, _defineProperty2.default)(_classnames2, 'is-image-fill', imageFill), _classnames2));
  var backgroundStyles = imageFill ? (0, _mediaContainer.imageFillStyles)(mediaUrl, focalPoint) : {};
  var gridTemplateColumns;

  if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
    gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
  }

  var style = {
    gridTemplateColumns: gridTemplateColumns
  };
  return (0, _element.createElement)("div", {
    className: className,
    style: style
  }, (0, _element.createElement)("figure", {
    className: "wp-block-media-text__media",
    style: backgroundStyles
  }, (mediaTypeRenders[mediaType] || _lodash.noop)()), (0, _element.createElement)("div", {
    className: "wp-block-media-text__content"
  }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
}
//# sourceMappingURL=save.js.map