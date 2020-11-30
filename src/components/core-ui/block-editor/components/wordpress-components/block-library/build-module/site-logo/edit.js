import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { includes, pick } from 'lodash';
/**
 * WordPress dependencies
 */

import { isBlobURL } from '@wordpress/blob';
import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice, PanelBody, RangeControl, ResizableBox, Spinner, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useViewportMatch } from '@wordpress/compose';
import { BlockControls, BlockIcon, InspectorControls, MediaPlaceholder, MediaReplaceFlow, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { trash } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import icon from './icon';
import useClientWidth from '../image/use-client-width';
/**
 * Module constants
 */

import { MIN_SIZE } from '../image/constants';
var ALLOWED_MEDIA_TYPES = ['image'];
var ACCEPT_MEDIA_STRING = 'image/*';

var SiteLogo = function SiteLogo(_ref) {
  var alt = _ref.alt,
      _ref$attributes = _ref.attributes,
      align = _ref$attributes.align,
      width = _ref$attributes.width,
      height = _ref$attributes.height,
      containerRef = _ref.containerRef,
      isSelected = _ref.isSelected,
      setAttributes = _ref.setAttributes,
      logoUrl = _ref.logoUrl,
      siteUrl = _ref.siteUrl;
  var clientWidth = useClientWidth(containerRef, [align]);
  var isLargeViewport = useViewportMatch('medium');
  var isWideAligned = includes(['wide', 'full'], align);
  var isResizable = !isWideAligned && isLargeViewport;

  var _useState = useState({}),
      _useState2 = _slicedToArray(_useState, 2),
      _useState2$ = _useState2[0],
      naturalWidth = _useState2$.naturalWidth,
      naturalHeight = _useState2$.naturalHeight,
      setNaturalSize = _useState2[1];

  var _useDispatch = useDispatch('core/block-editor'),
      toggleSelection = _useDispatch.toggleSelection;

  var classes = classnames({
    'is-transient': isBlobURL(logoUrl)
  });

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    var siteEntities = select('core').getEditedEntityRecord('root', 'site');
    return _objectSpread({
      title: siteEntities.title
    }, pick(getSettings(), ['imageSizes', 'isRTL', 'maxWidth']));
  }),
      maxWidth = _useSelect.maxWidth,
      isRTL = _useSelect.isRTL,
      title = _useSelect.title;

  function onResizeStart() {
    toggleSelection(false);
  }

  function _onResizeStop() {
    toggleSelection(true);
  }

  var img = // Disable reason: Image itself is not meant to be interactive, but
  // should direct focus to block.

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  createElement("a", {
    href: siteUrl,
    className: classes,
    rel: "home",
    title: title,
    onClick: function onClick(event) {
      return event.preventDefault();
    }
  }, createElement("span", {
    className: "custom-logo-link"
  }, createElement("img", {
    className: "custom-logo",
    src: logoUrl,
    alt: alt,
    onLoad: function onLoad(event) {
      setNaturalSize(pick(event.target, ['naturalWidth', 'naturalHeight']));
    }
  })))
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  ;
  var imageWidthWithinContainer;

  if (clientWidth && naturalWidth && naturalHeight) {
    var exceedMaxWidth = naturalWidth > clientWidth;
    imageWidthWithinContainer = exceedMaxWidth ? clientWidth : naturalWidth;
  }

  if (!isResizable || !imageWidthWithinContainer) {
    return createElement("div", {
      style: {
        width: width,
        height: height
      }
    }, img);
  }

  var currentWidth = width || imageWidthWithinContainer;
  var ratio = naturalWidth / naturalHeight;
  var currentHeight = currentWidth / ratio;
  var minWidth = naturalWidth < naturalHeight ? MIN_SIZE : MIN_SIZE * ratio;
  var minHeight = naturalHeight < naturalWidth ? MIN_SIZE : MIN_SIZE / ratio; // With the current implementation of ResizableBox, an image needs an
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


  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Site Logo Settings')
  }, createElement(RangeControl, {
    label: __('Image width'),
    onChange: function onChange(newWidth) {
      return setAttributes({
        width: newWidth
      });
    },
    min: minWidth,
    max: maxWidthBuffer,
    initialPosition: Math.min(naturalWidth, maxWidthBuffer),
    value: width || '',
    disabled: !isResizable
  }))), createElement(ResizableBox, {
    size: {
      width: width,
      height: height
    },
    showHandle: isSelected,
    minWidth: minWidth,
    maxWidth: maxWidthBuffer,
    minHeight: minHeight,
    maxHeight: maxWidthBuffer / ratio,
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
  }, img));
};

export default function LogoEdit(_ref2) {
  var attributes = _ref2.attributes,
      className = _ref2.className,
      setAttributes = _ref2.setAttributes,
      isSelected = _ref2.isSelected;
  var width = attributes.width;

  var _useState3 = useState(),
      _useState4 = _slicedToArray(_useState3, 2),
      logoUrl = _useState4[0],
      setLogoUrl = _useState4[1];

  var _useState5 = useState(),
      _useState6 = _slicedToArray(_useState5, 2),
      error = _useState6[0],
      setError = _useState6[1];

  var ref = useRef();

  var _useSelect2 = useSelect(function (select) {
    var siteSettings = select('core').getEditedEntityRecord('root', 'site');
    var mediaItem = select('core').getEntityRecord('root', 'media', siteSettings.sitelogo);
    return {
      mediaItemData: mediaItem && {
        url: mediaItem.source_url,
        alt: mediaItem.alt_text
      },
      sitelogo: siteSettings.sitelogo,
      url: siteSettings.url
    };
  }, []),
      mediaItemData = _useSelect2.mediaItemData,
      sitelogo = _useSelect2.sitelogo,
      url = _useSelect2.url;

  var _useDispatch2 = useDispatch('core'),
      editEntityRecord = _useDispatch2.editEntityRecord;

  var setLogo = function setLogo(newValue) {
    return editEntityRecord('root', 'site', undefined, {
      sitelogo: newValue
    });
  };

  var alt = null;

  if (mediaItemData) {
    alt = mediaItemData.alt;

    if (logoUrl !== mediaItemData.url) {
      setLogoUrl(mediaItemData.url);
    }
  }

  var onSelectLogo = function onSelectLogo(media) {
    if (!media) {
      return;
    }

    if (!media.id && media.url) {
      // This is a temporary blob image
      setLogo('');
      setError();
      setLogoUrl(media.url);
      return;
    }

    setLogo(media.id.toString());
  };

  var deleteLogo = function deleteLogo() {
    setLogo('');
    setLogoUrl('');
  };

  var onUploadError = function onUploadError(message) {
    setError(message[2] ? message[2] : null);
  };

  var controls = createElement(BlockControls, null, createElement(ToolbarGroup, null, logoUrl && createElement(MediaReplaceFlow, {
    mediaURL: logoUrl,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: ACCEPT_MEDIA_STRING,
    onSelect: onSelectLogo,
    onError: onUploadError
  }), !!logoUrl && createElement(ToolbarButton, {
    icon: trash,
    onClick: function onClick() {
      return deleteLogo();
    },
    label: __('Delete Site Logo')
  })));

  var label = __('Site Logo');

  var logoImage;

  if (sitelogo === undefined) {
    logoImage = createElement(Spinner, null);
  }

  if (!!logoUrl) {
    logoImage = createElement(SiteLogo, {
      alt: alt,
      attributes: attributes,
      className: className,
      containerRef: ref,
      isSelected: isSelected,
      setAttributes: setAttributes,
      logoUrl: logoUrl,
      siteUrl: url
    });
  }

  var mediaPlaceholder = createElement(MediaPlaceholder, {
    icon: createElement(BlockIcon, {
      icon: icon
    }),
    labels: {
      title: label,
      instructions: __('Upload an image, or pick one from your media library, to be your site logo')
    },
    onSelect: onSelectLogo,
    accept: ACCEPT_MEDIA_STRING,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    mediaPreview: logoImage,
    notices: error && createElement(Notice, {
      status: "error",
      isDismissible: false
    }, error),
    onError: onUploadError
  });
  var classes = classnames(className, {
    'is-resized': !!width,
    'is-focused': isSelected
  });
  var key = !!logoUrl;
  var blockWrapperProps = useBlockWrapperProps({
    ref: ref,
    className: classes,
    key: key
  });
  return createElement("div", blockWrapperProps, controls, logoUrl && logoImage, !logoUrl && mediaPlaceholder);
}
//# sourceMappingURL=edit.js.map