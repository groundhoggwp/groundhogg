import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { map, filter } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, _x } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { BlockControls, BlockVerticalAlignmentToolbar, InnerBlocks, InspectorControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps, __experimentalImageURLInputUI as ImageURLInputUI, __experimentalImageSizeControl as ImageSizeControl } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, ToggleControl, ToolbarGroup, ExternalLink, FocalPointPicker } from '@wordpress/components';
import { pullLeft, pullRight } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import MediaContainer from './media-container';
import { DEFAULT_MEDIA_SIZE_SLUG } from './constants';
/**
 * Constants
 */

var TEMPLATE = [['core/paragraph', {
  fontSize: 'large',
  placeholder: _x('Content…', 'content placeholder')
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
  var mediaSizeSlug = attributes.mediaSizeSlug || DEFAULT_MEDIA_SIZE_SLUG;
  var image = useSelect(function (select) {
    return mediaId && isSelected ? select('core').getMedia(mediaId) : null;
  }, [isSelected, mediaId]);

  var _useState = useState(null),
      _useState2 = _slicedToArray(_useState, 2),
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

  var classNames = classnames((_classnames = {
    'has-media-on-the-right': 'right' === mediaPosition,
    'is-selected': isSelected,
    'is-stacked-on-mobile': isStackedOnMobile
  }, _defineProperty(_classnames, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), _defineProperty(_classnames, 'is-image-fill', imageFill), _classnames));
  var widthString = "".concat(temporaryMediaWidth || mediaWidth, "%");
  var gridTemplateColumns = 'right' === mediaPosition ? "1fr ".concat(widthString) : "".concat(widthString, " 1fr");
  var style = {
    gridTemplateColumns: gridTemplateColumns,
    msGridColumns: gridTemplateColumns
  };
  var toolbarControls = [{
    icon: pullLeft,
    title: __('Show media on left'),
    isActive: mediaPosition === 'left',
    onClick: function onClick() {
      return setAttributes({
        mediaPosition: 'left'
      });
    }
  }, {
    icon: pullRight,
    title: __('Show media on right'),
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

  var imageSizes = useSelect(function (select) {
    var settings = select('core/block-editor').getSettings();
    return settings === null || settings === void 0 ? void 0 : settings.imageSizes;
  });
  var imageSizeOptions = map(filter(imageSizes, function (_ref3) {
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

  var mediaTextGeneralSettings = createElement(PanelBody, {
    title: __('Media & Text settings')
  }, createElement(ToggleControl, {
    label: __('Stack on mobile'),
    checked: isStackedOnMobile,
    onChange: function onChange() {
      return setAttributes({
        isStackedOnMobile: !isStackedOnMobile
      });
    }
  }), mediaType === 'image' && createElement(ToggleControl, {
    label: __('Crop image to fill entire column'),
    checked: imageFill,
    onChange: function onChange() {
      return setAttributes({
        imageFill: !imageFill
      });
    }
  }), imageFill && createElement(FocalPointPicker, {
    label: __('Focal point picker'),
    url: mediaUrl,
    value: focalPoint,
    onChange: function onChange(value) {
      return setAttributes({
        focalPoint: value
      });
    }
  }), mediaType === 'image' && createElement(TextareaControl, {
    label: __('Alt text (alternative text)'),
    value: mediaAlt,
    onChange: onMediaAltChange,
    help: createElement(Fragment, null, createElement(ExternalLink, {
      href: "https://www.w3.org/WAI/tutorials/images/decision-tree"
    }, __('Describe the purpose of the image')), __('Leave empty if the image is purely decorative.'))
  }), mediaType === 'image' && createElement(ImageSizeControl, {
    onChangeImage: updateImage,
    slug: mediaSizeSlug,
    imageSizeOptions: imageSizeOptions,
    isResizable: false
  }));
  var blockWrapperProps = useBlockWrapperProps({
    className: classNames,
    style: style
  });
  return createElement(Fragment, null, createElement(InspectorControls, null, mediaTextGeneralSettings), createElement(BlockControls, null, createElement(ToolbarGroup, {
    controls: toolbarControls
  }), createElement(BlockVerticalAlignmentToolbar, {
    onChange: onVerticalAlignmentChange,
    value: verticalAlignment
  }), mediaType === 'image' && createElement(ToolbarGroup, null, createElement(ImageURLInputUI, {
    url: href || '',
    onChangeUrl: onSetHref,
    linkDestination: linkDestination,
    mediaType: mediaType,
    mediaUrl: image && image.source_url,
    mediaLink: image && image.link,
    linkTarget: linkTarget,
    linkClass: linkClass,
    rel: rel
  }))), createElement("div", blockWrapperProps, createElement(MediaContainer, _extends({
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
  })), createElement(InnerBlocks, {
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-media-text__content'
    },
    template: TEMPLATE,
    templateInsertUpdatesSelection: false
  })));
}

export default MediaTextEdit;
//# sourceMappingURL=edit.js.map