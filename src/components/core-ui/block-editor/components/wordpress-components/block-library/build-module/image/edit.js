import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { get, omit, pick } from 'lodash';
/**
 * WordPress dependencies
 */

import { getBlobByURL, isBlobURL, revokeBlobURL } from '@wordpress/blob';
import { withNotices } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { BlockAlignmentToolbar, BlockControls, BlockIcon, MediaPlaceholder, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { image as icon } from '@wordpress/icons';
/* global wp */

/**
 * Internal dependencies
 */

import Image from './image';
/**
 * Module constants
 */

import { LINK_DESTINATION_ATTACHMENT, LINK_DESTINATION_CUSTOM, LINK_DESTINATION_MEDIA, LINK_DESTINATION_NONE, ALLOWED_MEDIA_TYPES, DEFAULT_SIZE_SLUG } from './constants';
export var pickRelevantMediaFiles = function pickRelevantMediaFiles(image) {
  var imageProps = pick(image, ['alt', 'id', 'link', 'caption']);
  imageProps.url = get(image, ['sizes', 'large', 'url']) || get(image, ['media_details', 'sizes', 'large', 'source_url']) || image.url;
  return imageProps;
};
/**
 * Is the URL a temporary blob URL? A blob URL is one that is used temporarily
 * while the image is being uploaded and will not have an id yet allocated.
 *
 * @param {number=} id The id of the image.
 * @param {string=} url The url of the image.
 *
 * @return {boolean} Is the URL a Blob URL
 */

var isTemporaryImage = function isTemporaryImage(id, url) {
  return !id && isBlobURL(url);
};
/**
 * Is the url for the image hosted externally. An externally hosted image has no
 * id and is not a blob url.
 *
 * @param {number=} id  The id of the image.
 * @param {string=} url The url of the image.
 *
 * @return {boolean} Is the url an externally hosted url?
 */


export var isExternalImage = function isExternalImage(id, url) {
  return url && !id && !isBlobURL(url);
};
export function ImageEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      className = _ref.className,
      noticeUI = _ref.noticeUI,
      insertBlocksAfter = _ref.insertBlocksAfter,
      noticeOperations = _ref.noticeOperations,
      onReplace = _ref.onReplace;
  var _attributes$url = attributes.url,
      url = _attributes$url === void 0 ? '' : _attributes$url,
      alt = attributes.alt,
      caption = attributes.caption,
      align = attributes.align,
      id = attributes.id,
      width = attributes.width,
      height = attributes.height,
      sizeSlug = attributes.sizeSlug;
  var altRef = useRef();
  useEffect(function () {
    altRef.current = alt;
  }, [alt]);
  var captionRef = useRef();
  useEffect(function () {
    captionRef.current = caption;
  }, [caption]);
  var ref = useRef();
  var mediaUpload = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return getSettings().mediaUpload;
  });

  function onUploadError(message) {
    noticeOperations.removeAllNotices();
    noticeOperations.createErrorNotice(message);
  }

  function onSelectImage(media) {
    var _wp, _wp$media, _wp$media$view, _wp$media$view$settin, _wp$media$view$settin2;

    if (!media || !media.url) {
      setAttributes({
        url: undefined,
        alt: undefined,
        id: undefined,
        title: undefined,
        caption: undefined
      });
      return;
    }

    var mediaAttributes = pickRelevantMediaFiles(media); // If the current image is temporary but an alt text was meanwhile
    // written by the user, make sure the text is not overwritten.

    if (isTemporaryImage(id, url)) {
      if (altRef.current) {
        mediaAttributes = omit(mediaAttributes, ['alt']);
      }
    } // If a caption text was meanwhile written by the user,
    // make sure the text is not overwritten by empty captions.


    if (captionRef.current && !get(mediaAttributes, ['caption'])) {
      mediaAttributes = omit(mediaAttributes, ['caption']);
    }

    var additionalAttributes; // Reset the dimension attributes if changing to a different image.

    if (!media.id || media.id !== id) {
      additionalAttributes = {
        width: undefined,
        height: undefined,
        sizeSlug: DEFAULT_SIZE_SLUG
      };
    } else {
      // Keep the same url when selecting the same file, so "Image Size"
      // option is not changed.
      additionalAttributes = {
        url: url
      };
    } // Check if default link setting should be used.


    var linkDestination = attributes.linkDestination;

    if (!linkDestination) {
      // Use the WordPress option to determine the proper default.
      // The constants used in Gutenberg do not match WP options so a little more complicated than ideal.
      // TODO: fix this in a follow up PR, requires updating media-text and ui component.
      switch (((_wp = wp) === null || _wp === void 0 ? void 0 : (_wp$media = _wp.media) === null || _wp$media === void 0 ? void 0 : (_wp$media$view = _wp$media.view) === null || _wp$media$view === void 0 ? void 0 : (_wp$media$view$settin = _wp$media$view.settings) === null || _wp$media$view$settin === void 0 ? void 0 : (_wp$media$view$settin2 = _wp$media$view$settin.defaultProps) === null || _wp$media$view$settin2 === void 0 ? void 0 : _wp$media$view$settin2.link) || LINK_DESTINATION_NONE) {
        case 'file':
        case LINK_DESTINATION_MEDIA:
          linkDestination = LINK_DESTINATION_MEDIA;
          break;

        case 'post':
        case LINK_DESTINATION_ATTACHMENT:
          linkDestination = LINK_DESTINATION_ATTACHMENT;
          break;

        case LINK_DESTINATION_CUSTOM:
          linkDestination = LINK_DESTINATION_CUSTOM;
          break;

        case LINK_DESTINATION_NONE:
          linkDestination = LINK_DESTINATION_NONE;
          break;
      }
    } // Check if the image is linked to it's media.


    var href;

    switch (linkDestination) {
      case LINK_DESTINATION_MEDIA:
        href = media.url;
        break;

      case LINK_DESTINATION_ATTACHMENT:
        href = media.link;
        break;
    }

    mediaAttributes.href = href;
    setAttributes(_objectSpread(_objectSpread(_objectSpread({}, mediaAttributes), additionalAttributes), {}, {
      linkDestination: linkDestination
    }));
  }

  function onSelectURL(newURL) {
    if (newURL !== url) {
      setAttributes({
        url: newURL,
        id: undefined,
        sizeSlug: DEFAULT_SIZE_SLUG
      });
    }
  }

  function updateAlignment(nextAlign) {
    var extraUpdatedAttributes = ['wide', 'full'].includes(nextAlign) ? {
      width: undefined,
      height: undefined
    } : {};
    setAttributes(_objectSpread(_objectSpread({}, extraUpdatedAttributes), {}, {
      align: nextAlign
    }));
  }

  var isTemp = isTemporaryImage(id, url); // Upload a temporary image on mount.

  useEffect(function () {
    if (!isTemp) {
      return;
    }

    var file = getBlobByURL(url);

    if (file) {
      mediaUpload({
        filesList: [file],
        onFileChange: function onFileChange(_ref2) {
          var _ref3 = _slicedToArray(_ref2, 1),
              img = _ref3[0];

          onSelectImage(img);
        },
        allowedTypes: ALLOWED_MEDIA_TYPES,
        onError: function onError(message) {
          noticeOperations.createErrorNotice(message);
          setAttributes({
            src: undefined,
            id: undefined,
            url: undefined
          });
        }
      });
    }
  }, []); // If an image is temporary, revoke the Blob url when it is uploaded (and is
  // no longer temporary).

  useEffect(function () {
    if (!isTemp) {
      return;
    }

    return function () {
      revokeBlobURL(url);
    };
  }, [isTemp]);
  var isExternal = isExternalImage(id, url);
  var controls = createElement(BlockControls, null, createElement(BlockAlignmentToolbar, {
    value: align,
    onChange: updateAlignment
  }));
  var src = isExternal ? url : undefined;
  var mediaPreview = !!url && createElement("img", {
    alt: __('Edit image'),
    title: __('Edit image'),
    className: 'edit-image-preview',
    src: url
  });
  var mediaPlaceholder = createElement(MediaPlaceholder, {
    icon: createElement(BlockIcon, {
      icon: icon
    }),
    onSelect: onSelectImage,
    onSelectURL: onSelectURL,
    notices: noticeUI,
    onError: onUploadError,
    accept: "image/*",
    allowedTypes: ALLOWED_MEDIA_TYPES,
    value: {
      id: id,
      src: src
    },
    mediaPreview: mediaPreview,
    disableMediaButtons: url
  });
  var classes = classnames(className, _defineProperty({
    'is-transient': isBlobURL(url),
    'is-resized': !!width || !!height,
    'is-focused': isSelected
  }, "size-".concat(sizeSlug), sizeSlug));
  var blockWrapperProps = useBlockWrapperProps({
    ref: ref,
    className: classes
  });
  return createElement(Fragment, null, controls, createElement("figure", blockWrapperProps, url && createElement(Image, {
    attributes: attributes,
    setAttributes: setAttributes,
    isSelected: isSelected,
    insertBlocksAfter: insertBlocksAfter,
    onReplace: onReplace,
    onSelectImage: onSelectImage,
    onSelectURL: onSelectURL,
    onUploadError: onUploadError,
    containerRef: ref
  }), mediaPlaceholder));
}
export default withNotices(ImageEdit);
//# sourceMappingURL=edit.js.map