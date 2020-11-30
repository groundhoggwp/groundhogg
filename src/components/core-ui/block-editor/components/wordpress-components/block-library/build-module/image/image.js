import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { get, filter, map, last, pick, includes } from 'lodash';
/**
 * WordPress dependencies
 */

import { isBlobURL } from '@wordpress/blob';
import { ExternalLink, PanelBody, ResizableBox, Spinner, TextareaControl, TextControl, ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useViewportMatch, usePrevious } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { BlockControls, InspectorControls, InspectorAdvancedControls, RichText, __experimentalImageSizeControl as ImageSizeControl, __experimentalImageURLInputUI as ImageURLInputUI, MediaReplaceFlow } from '@wordpress/block-editor';
import { useEffect, useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { getPath } from '@wordpress/url';
import { createBlock } from '@wordpress/blocks';
import { crop, upload } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { createUpgradedEmbedBlock } from '../embed/util';
import useClientWidth from './use-client-width';
import ImageEditor from './image-editor';
import { isExternalImage } from './edit';
/**
 * Module constants
 */

import { MIN_SIZE, ALLOWED_MEDIA_TYPES } from './constants';

function getFilename(url) {
  var path = getPath(url);

  if (path) {
    return last(path.split('/'));
  }
}

export default function Image(_ref) {
  var _ref$attributes = _ref.attributes,
      _ref$attributes$url = _ref$attributes.url,
      url = _ref$attributes$url === void 0 ? '' : _ref$attributes$url,
      alt = _ref$attributes.alt,
      caption = _ref$attributes.caption,
      align = _ref$attributes.align,
      id = _ref$attributes.id,
      href = _ref$attributes.href,
      rel = _ref$attributes.rel,
      linkClass = _ref$attributes.linkClass,
      linkDestination = _ref$attributes.linkDestination,
      title = _ref$attributes.title,
      width = _ref$attributes.width,
      height = _ref$attributes.height,
      linkTarget = _ref$attributes.linkTarget,
      sizeSlug = _ref$attributes.sizeSlug,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      insertBlocksAfter = _ref.insertBlocksAfter,
      onReplace = _ref.onReplace,
      onSelectImage = _ref.onSelectImage,
      onSelectURL = _ref.onSelectURL,
      onUploadError = _ref.onUploadError,
      containerRef = _ref.containerRef;
  var captionRef = useRef();
  var prevUrl = usePrevious(url);
  var image = useSelect(function (select) {
    var _select = select('core'),
        getMedia = _select.getMedia;

    return id && isSelected ? getMedia(id) : null;
  }, [id, isSelected]);

  var _useSelect = useSelect(function (select) {
    var _select2 = select('core/block-editor'),
        getSettings = _select2.getSettings;

    return pick(getSettings(), ['imageEditing', 'imageSizes', 'isRTL', 'maxWidth', 'mediaUpload']);
  }),
      imageEditing = _useSelect.imageEditing,
      imageSizes = _useSelect.imageSizes,
      isRTL = _useSelect.isRTL,
      maxWidth = _useSelect.maxWidth,
      mediaUpload = _useSelect.mediaUpload;

  var _useDispatch = useDispatch('core/block-editor'),
      toggleSelection = _useDispatch.toggleSelection;

  var _useDispatch2 = useDispatch('core/notices'),
      createErrorNotice = _useDispatch2.createErrorNotice,
      createSuccessNotice = _useDispatch2.createSuccessNotice;

  var isLargeViewport = useViewportMatch('medium');

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      captionFocused = _useState2[0],
      setCaptionFocused = _useState2[1];

  var isWideAligned = includes(['wide', 'full'], align);

  var _useState3 = useState({}),
      _useState4 = _slicedToArray(_useState3, 2),
      _useState4$ = _useState4[0],
      naturalWidth = _useState4$.naturalWidth,
      naturalHeight = _useState4$.naturalHeight,
      setNaturalSize = _useState4[1];

  var _useState5 = useState(false),
      _useState6 = _slicedToArray(_useState5, 2),
      isEditingImage = _useState6[0],
      setIsEditingImage = _useState6[1];

  var _useState7 = useState(),
      _useState8 = _slicedToArray(_useState7, 2),
      externalBlob = _useState8[0],
      setExternalBlob = _useState8[1];

  var clientWidth = useClientWidth(containerRef, [align]);
  var isResizable = !isWideAligned && isLargeViewport;
  var imageSizeOptions = map(filter(imageSizes, function (_ref2) {
    var slug = _ref2.slug;
    return get(image, ['media_details', 'sizes', slug, 'source_url']);
  }), function (_ref3) {
    var name = _ref3.name,
        slug = _ref3.slug;
    return {
      value: slug,
      label: name
    };
  });
  useEffect(function () {
    if (!isSelected) {
      setCaptionFocused(false);
    }
  }, [isSelected]); // If an image is externally hosted, try to fetch the image data. This may
  // fail if the image host doesn't allow CORS with the domain. If it works,
  // we can enable a button in the toolbar to upload the image.

  useEffect(function () {
    if (!isExternalImage(id, url) || !isSelected || externalBlob) {
      return;
    }

    window.fetch(url).then(function (response) {
      return response.blob();
    }).then(function (blob) {
      return setExternalBlob(blob);
    });
  }, [id, url, isSelected, externalBlob]); // Focus the caption after inserting an image from the placeholder. This is
  // done to preserve the behaviour of focussing the first tabbable element
  // when a block is mounted. Previously, the image block would remount when
  // the placeholder is removed. Maybe this behaviour could be removed.

  useEffect(function () {
    if (url && !prevUrl && isSelected) {
      captionRef.current.focus();
    }
  }, [url, prevUrl]);

  function onResizeStart() {
    toggleSelection(false);
  }

  function _onResizeStop() {
    toggleSelection(true);
  }

  function onImageError() {
    // Check if there's an embed block that handles this URL.
    var embedBlock = createUpgradedEmbedBlock({
      attributes: {
        url: url
      }
    });

    if (undefined !== embedBlock) {
      onReplace(embedBlock);
    }
  }

  function onSetHref(props) {
    setAttributes(props);
  }

  function onSetTitle(value) {
    // This is the HTML title attribute, separate from the media object
    // title.
    setAttributes({
      title: value
    });
  }

  function onFocusCaption() {
    if (!captionFocused) {
      setCaptionFocused(true);
    }
  }

  function onImageClick() {
    if (captionFocused) {
      setCaptionFocused(false);
    }
  }

  function updateAlt(newAlt) {
    setAttributes({
      alt: newAlt
    });
  }

  function updateImage(newSizeSlug) {
    var newUrl = get(image, ['media_details', 'sizes', newSizeSlug, 'source_url']);

    if (!newUrl) {
      return null;
    }

    setAttributes({
      url: newUrl,
      width: undefined,
      height: undefined,
      sizeSlug: newSizeSlug
    });
  }

  function uploadExternal() {
    mediaUpload({
      filesList: [externalBlob],
      onFileChange: function onFileChange(_ref4) {
        var _ref5 = _slicedToArray(_ref4, 1),
            img = _ref5[0];

        onSelectImage(img);

        if (isBlobURL(img.url)) {
          return;
        }

        setExternalBlob();
        createSuccessNotice(__('Image uploaded.'), {
          type: 'snackbar'
        });
      },
      allowedTypes: ALLOWED_MEDIA_TYPES,
      onError: function onError(message) {
        createErrorNotice(message, {
          type: 'snackbar'
        });
      }
    });
  }

  useEffect(function () {
    if (!isSelected) {
      setIsEditingImage(false);
    }
  }, [isSelected]);
  var canEditImage = id && naturalWidth && naturalHeight && imageEditing;
  var controls = createElement(Fragment, null, createElement(BlockControls, null, !isEditingImage && createElement(ToolbarGroup, null, createElement(ImageURLInputUI, {
    url: href || '',
    onChangeUrl: onSetHref,
    linkDestination: linkDestination,
    mediaUrl: image && image.source_url,
    mediaLink: image && image.link,
    linkTarget: linkTarget,
    linkClass: linkClass,
    rel: rel
  })), canEditImage && !isEditingImage && createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    onClick: function onClick() {
      return setIsEditingImage(true);
    },
    icon: crop,
    label: __('Crop')
  })), externalBlob && createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    onClick: uploadExternal,
    icon: upload,
    label: __('Upload external image')
  })), !isEditingImage && createElement(MediaReplaceFlow, {
    mediaId: id,
    mediaURL: url,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "image/*",
    onSelect: onSelectImage,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Image settings')
  }, createElement(TextareaControl, {
    label: __('Alt text (alternative text)'),
    value: alt,
    onChange: updateAlt,
    help: createElement(Fragment, null, createElement(ExternalLink, {
      href: "https://www.w3.org/WAI/tutorials/images/decision-tree"
    }, __('Describe the purpose of the image')), __('Leave empty if the image is purely decorative.'))
  }), createElement(ImageSizeControl, {
    onChangeImage: updateImage,
    onChange: function onChange(value) {
      return setAttributes(value);
    },
    slug: sizeSlug,
    width: width,
    height: height,
    imageSizeOptions: imageSizeOptions,
    isResizable: isResizable,
    imageWidth: naturalWidth,
    imageHeight: naturalHeight
  }))), createElement(InspectorAdvancedControls, null, createElement(TextControl, {
    label: __('Title attribute'),
    value: title || '',
    onChange: onSetTitle,
    help: createElement(Fragment, null, __('Describe the role of this image on the page.'), createElement(ExternalLink, {
      href: "https://www.w3.org/TR/html52/dom.html#the-title-attribute"
    }, __('(Note: many devices and browsers do not display this text.)')))
  })));
  var filename = getFilename(url);
  var defaultedAlt;

  if (alt) {
    defaultedAlt = alt;
  } else if (filename) {
    defaultedAlt = sprintf(
    /* translators: %s: file name */
    __('This image has an empty alt attribute; its file name is %s'), filename);
  } else {
    defaultedAlt = __('This image has an empty alt attribute');
  }

  var img = // Disable reason: Image itself is not meant to be interactive, but
  // should direct focus to block.

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  createElement(Fragment, null, createElement("img", {
    src: url,
    alt: defaultedAlt,
    onClick: onImageClick,
    onError: function onError() {
      return onImageError();
    },
    onLoad: function onLoad(event) {
      setNaturalSize(pick(event.target, ['naturalWidth', 'naturalHeight']));
    }
  }), isBlobURL(url) && createElement(Spinner, null))
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  ;
  var imageWidthWithinContainer;
  var imageHeightWithinContainer;

  if (clientWidth && naturalWidth && naturalHeight) {
    var exceedMaxWidth = naturalWidth > clientWidth;
    var ratio = naturalHeight / naturalWidth;
    imageWidthWithinContainer = exceedMaxWidth ? clientWidth : naturalWidth;
    imageHeightWithinContainer = exceedMaxWidth ? clientWidth * ratio : naturalHeight;
  }

  if (canEditImage && isEditingImage) {
    img = createElement(ImageEditor, {
      id: id,
      url: url,
      setAttributes: setAttributes,
      naturalWidth: naturalWidth,
      naturalHeight: naturalHeight,
      width: width,
      height: height,
      clientWidth: clientWidth,
      setIsEditingImage: setIsEditingImage
    });
  } else if (!isResizable || !imageWidthWithinContainer) {
    img = createElement("div", {
      style: {
        width: width,
        height: height
      }
    }, img);
  } else {
    var currentWidth = width || imageWidthWithinContainer;
    var currentHeight = height || imageHeightWithinContainer;

    var _ratio = naturalWidth / naturalHeight;

    var minWidth = naturalWidth < naturalHeight ? MIN_SIZE : MIN_SIZE * _ratio;
    var minHeight = naturalHeight < naturalWidth ? MIN_SIZE : MIN_SIZE / _ratio; // With the current implementation of ResizableBox, an image needs an
    // explicit pixel value for the max-width. In absence of being able to
    // set the content-width, this max-width is currently dictated by the
    // vanilla editor style. The following variable adds a buffer to this
    // vanilla style, so 3rd party themes have some wiggleroom. This does,
    // in most cases, allow you to scale the image beyond the width of the
    // main column, though not infinitely.
    // @todo It would be good to revisit this once a content-width variable
    // becomes available.

    var maxWidthBuffer = maxWidth * 2.5;
    var showRightHandle = false;
    var showLeftHandle = false;
    /* eslint-disable no-lonely-if */
    // See https://github.com/WordPress/gutenberg/issues/7584.

    if (align === 'center') {
      // When the image is centered, show both handles.
      showRightHandle = true;
      showLeftHandle = true;
    } else if (isRTL) {
      // In RTL mode the image is on the right by default.
      // Show the right handle and hide the left handle only when it is
      // aligned left. Otherwise always show the left handle.
      if (align === 'left') {
        showRightHandle = true;
      } else {
        showLeftHandle = true;
      }
    } else {
      // Show the left handle and hide the right handle only when the
      // image is aligned right. Otherwise always show the right handle.
      if (align === 'right') {
        showLeftHandle = true;
      } else {
        showRightHandle = true;
      }
    }
    /* eslint-enable no-lonely-if */


    img = createElement(ResizableBox, {
      size: {
        width: width,
        height: height
      },
      showHandle: isSelected,
      minWidth: minWidth,
      maxWidth: maxWidthBuffer,
      minHeight: minHeight,
      maxHeight: maxWidthBuffer / _ratio,
      lockAspectRatio: true,
      enable: {
        top: false,
        right: showRightHandle,
        bottom: true,
        left: showLeftHandle
      },
      onResizeStart: onResizeStart,
      onResizeStop: function onResizeStop(event, direction, elt, delta) {
        _onResizeStop();

        setAttributes({
          width: parseInt(currentWidth + delta.width, 10),
          height: parseInt(currentHeight + delta.height, 10)
        });
      }
    }, img);
  }

  return createElement(Fragment, null, controls, img, (!RichText.isEmpty(caption) || isSelected) && createElement(RichText, {
    ref: captionRef,
    tagName: "figcaption",
    placeholder: __('Write caption…'),
    value: caption,
    unstableOnFocus: onFocusCaption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    isSelected: captionFocused,
    inlineToolbar: true,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter(createBlock('core/paragraph'));
    }
  }));
}
//# sourceMappingURL=image.js.map