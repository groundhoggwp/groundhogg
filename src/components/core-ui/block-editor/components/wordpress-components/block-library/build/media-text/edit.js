"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _mediaContainer = _interopRequireDefault(require("./media-container"));

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

/**
 * Constants
 */
var TEMPLATE = [['core/paragraph', {
  fontSize: 'large',
  placeholder: (0, _i18n._x)('Content…', 'content placeholder')
}]]; // this limits the resize to a safe zone to avoid making broken layouts

var WIDTH_CONSTRAINT_PERCENTAGE = 15;

var applyWidthConstraints = function applyWidthConstraints(width) {
  return Math.max(WIDTH_CONSTRAINT_PERCENTAGE, Math.min(width, 100 - WIDTH_CONSTRAINT_PERCENTAGE));
};

var LINK_DESTINATION_MEDIA = 'media';
var LINK_DESTINATION_ATTACHMENT = 'attachment';

function getImageSourceUrlBySizeSlug(image, slug) {
  var _image$media_details, _image$media_details$, _image$media_details$2;

  // eslint-disable-next-line camelcase
  return image === null || image === void 0 ? void 0 : (_image$media_details = image.media_details) === null || _image$media_details === void 0 ? void 0 : (_image$media_details$ = _image$media_details.sizes) === null || _image$media_details$ === void 0 ? void 0 : (_image$media_details$2 = _image$media_details$[slug]) === null || _image$media_details$2 === void 0 ? void 0 : _image$media_details$2.source_url;
}

function attributesFromMedia(_ref) {
  var _ref$attributes = _ref.attributes,
      linkDestination = _ref$attributes.linkDestination,
      href = _ref$attributes.href,
      setAttributes = _ref.setAttributes;
  return function (media) {
    var mediaType;
    var src; // for media selections originated from a file upload.

    if (media.media_type) {
      if (media.media_type === 'image') {
        mediaType = 'image';
      } else {
        // only images and videos are accepted so if the media_type is not an image we can assume it is a video.
        // video contain the media type of 'file' in the object returned from the rest api.
        mediaType = 'video';
      }
    } else {
      // for media selections originated from existing files in the media library.
      mediaType = media.type;
    }

    if (mediaType === 'image') {
      var _media$sizes, _media$sizes$large, _media$media_details, _media$media_details$, _media$media_details$2;

      // Try the "large" size URL, falling back to the "full" size URL below.
      src = ((_media$sizes = media.sizes) === null || _media$sizes === void 0 ? void 0 : (_media$sizes$large = _media$sizes.large) === null || _media$sizes$large === void 0 ? void 0 : _media$sizes$large.url) || ( // eslint-disable-next-line camelcase
      (_media$media_details = media.media_details) === null || _media$media_details === void 0 ? void 0 : (_media$media_details$ = _media$media_details.sizes) === null || _media$media_details$ === void 0 ? void 0 : (_media$media_details$2 = _media$media_details$.large) === null || _media$media_details$2 === void 0 ? void 0 : _media$media_details$2.source_url);
    }

    var newHref = href;

    if (linkDestination === LINK_DESTINATION_MEDIA) {
      // Update the media link.
      newHref = media.url;
    } // Check if the image is linked to the attachment page.


    if (linkDestination === LINK_DESTINATION_ATTACHMENT) {
      // Update the media link.
      newHref = media.link;
    }

    setAttributes({
      mediaAlt: media.alt,
      mediaId: media.id,
      mediaType: mediaType,
      mediaUrl: src || media.url,
      mediaLink: media.link || undefined,
      href: newHref,
      focalPoint: undefined
    });
  };
}

function MediaTextEdit(_ref2) {
  var _classnames;

  var attributes = _ref2.attributes,
      isSelected = _ref2.isSelected,
      setAttributes = _ref2.setAttributes;
  var focalPoint = attributes.focalPoint,
      href = attributes.href,
      imageFill = attributes.imageFill,
      isStackedOnMobile = attributes.isStackedOnMobile,
      linkClass = attributes.linkClass,
      linkDestination = attributes.linkDestination,
      linkTarget = attributes.linkTarget,
      mediaAlt = attributes.mediaAlt,
      mediaId = attributes.mediaId,
      mediaPosition = attributes.mediaPosition,
      mediaType = attributes.mediaType,
      mediaUrl = attributes.mediaUrl,
      mediaWidth = attributes.mediaWidth,
      rel = attributes.rel,
      verticalAlignment = attributes.verticalAlignment;
  var mediaSizeSlug = attributes.mediaSizeSlug || _constants.DEFAULT_MEDIA_SIZE_SLUG;
  var image = (0, _data.useSelect)(function (select) {
    return mediaId && isSelected ? select('core').getMedia(mediaId) : null;
  }, [isSelected, mediaId]);

  var _useState = (0, _element.useState)(null),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      temporaryMediaWidth = _useState2[0],
      setTemporaryMediaWidth = _useState2[1];

  var onSelectMedia = attributesFromMedia({
    attributes: attributes,
    setAttributes: setAttributes
  });

  var onSetHref = function onSetHref(props) {
    setAttributes(props);
  };

  var onWidthChange = function onWidthChange(width) {
    setTemporaryMediaWidth(applyWidthConstraints(width));
  };

  var commitWidthChange = function commitWidthChange(width) {
    setAttributes({
      mediaWidth: applyWidthConstraints(width)
    });
    setTemporaryMediaWidth(applyWidthConstraints(width));
  };

  var classNames = (0, _classnames2.default)((_classnames = {
    'has-media-on-the-right': 'right' === mediaPosition,
    'is-selected': isSelected,
    'is-stacked-on-mobile': isStackedOnMobile
  }, (0, _defineProperty2.default)(_classnames, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), (0, _defineProperty2.default)(_classnames, 'is-image-fill', imageFill), _classnames));
  var widthString = "".concat(temporaryMediaWidth || mediaWidth, "%");
  var gridTemplateColumns = 'right' === mediaPosition ? "1fr ".concat(widthString) : "".concat(widthString, " 1fr");
  var style = {
    gridTemplateColumns: gridTemplateColumns,
    msGridColumns: gridTemplateColumns
  };
  var toolbarControls = [{
    icon: _icons.pullLeft,
    title: (0, _i18n.__)('Show media on left'),
    isActive: mediaPosition === 'left',
    onClick: function onClick() {
      return setAttributes({
        mediaPosition: 'left'
      });
    }
  }, {
    icon: _icons.pullRight,
    title: (0, _i18n.__)('Show media on right'),
    isActive: mediaPosition === 'right',
    onClick: function onClick() {
      return setAttributes({
        mediaPosition: 'right'
      });
    }
  }];

  var onMediaAltChange = function onMediaAltChange(newMediaAlt) {
    setAttributes({
      mediaAlt: newMediaAlt
    });
  };

  var onVerticalAlignmentChange = function onVerticalAlignmentChange(alignment) {
    setAttributes({
      verticalAlignment: alignment
    });
  };

  var imageSizes = (0, _data.useSelect)(function (select) {
    var settings = select('core/block-editor').getSettings();
    return settings === null || settings === void 0 ? void 0 : settings.imageSizes;
  });
  var imageSizeOptions = (0, _lodash.map)((0, _lodash.filter)(imageSizes, function (_ref3) {
    var slug = _ref3.slug;
    return getImageSourceUrlBySizeSlug(image, slug);
  }), function (_ref4) {
    var name = _ref4.name,
        slug = _ref4.slug;
    return {
      value: slug,
      label: name
    };
  });

  var updateImage = function updateImage(newMediaSizeSlug) {
    var newUrl = getImageSourceUrlBySizeSlug(image, newMediaSizeSlug);

    if (!newUrl) {
      return null;
    }

    setAttributes({
      mediaUrl: newUrl,
      mediaSizeSlug: newMediaSizeSlug
    });
  };

  var mediaTextGeneralSettings = (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Media & Text settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Stack on mobile'),
    checked: isStackedOnMobile,
    onChange: function onChange() {
      return setAttributes({
        isStackedOnMobile: !isStackedOnMobile
      });
    }
  }), mediaType === 'image' && (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Crop image to fill entire column'),
    checked: imageFill,
    onChange: function onChange() {
      return setAttributes({
        imageFill: !imageFill
      });
    }
  }), imageFill && (0, _element.createElement)(_components.FocalPointPicker, {
    label: (0, _i18n.__)('Focal point picker'),
    url: mediaUrl,
    value: focalPoint,
    onChange: function onChange(value) {
      return setAttributes({
        focalPoint: value
      });
    }
  }), mediaType === 'image' && (0, _element.createElement)(_components.TextareaControl, {
    label: (0, _i18n.__)('Alt text (alternative text)'),
    value: mediaAlt,
    onChange: onMediaAltChange,
    help: (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ExternalLink, {
      href: "https://www.w3.org/WAI/tutorials/images/decision-tree"
    }, (0, _i18n.__)('Describe the purpose of the image')), (0, _i18n.__)('Leave empty if the image is purely decorative.'))
  }), mediaType === 'image' && (0, _element.createElement)(_blockEditor.__experimentalImageSizeControl, {
    onChangeImage: updateImage,
    slug: mediaSizeSlug,
    imageSizeOptions: imageSizeOptions,
    isResizable: false
  }));
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: classNames,
    style: style
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, mediaTextGeneralSettings), (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, {
    controls: toolbarControls
  }), (0, _element.createElement)(_blockEditor.BlockVerticalAlignmentToolbar, {
    onChange: onVerticalAlignmentChange,
    value: verticalAlignment
  }), mediaType === 'image' && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_blockEditor.__experimentalImageURLInputUI, {
    url: href || '',
    onChangeUrl: onSetHref,
    linkDestination: linkDestination,
    mediaType: mediaType,
    mediaUrl: image && image.source_url,
    mediaLink: image && image.link,
    linkTarget: linkTarget,
    linkClass: linkClass,
    rel: rel
  }))), (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_mediaContainer.default, (0, _extends2.default)({
    className: "wp-block-media-text__media",
    onSelectMedia: onSelectMedia,
    onWidthChange: onWidthChange,
    commitWidthChange: commitWidthChange
  }, {
    focalPoint: focalPoint,
    imageFill: imageFill,
    isSelected: isSelected,
    isStackedOnMobile: isStackedOnMobile,
    mediaAlt: mediaAlt,
    mediaId: mediaId,
    mediaPosition: mediaPosition,
    mediaType: mediaType,
    mediaUrl: mediaUrl,
    mediaWidth: mediaWidth
  })), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-media-text__content'
    },
    template: TEMPLATE,
    templateInsertUpdatesSelection: false
  })));
}

var _default = MediaTextEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map