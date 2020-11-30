"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LogoEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blob = require("@wordpress/blob");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _icon = _interopRequireDefault(require("./icon"));

var _useClientWidth = _interopRequireDefault(require("../image/use-client-width"));

var _constants = require("../image/constants");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
  var clientWidth = (0, _useClientWidth.default)(containerRef, [align]);
  var isLargeViewport = (0, _compose.useViewportMatch)('medium');
  var isWideAligned = (0, _lodash.includes)(['wide', 'full'], align);
  var isResizable = !isWideAligned && isLargeViewport;

  var _useState = (0, _element.useState)({}),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      _useState2$ = _useState2[0],
      naturalWidth = _useState2$.naturalWidth,
      naturalHeight = _useState2$.naturalHeight,
      setNaturalSize = _useState2[1];

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      toggleSelection = _useDispatch.toggleSelection;

  var classes = (0, _classnames.default)({
    'is-transient': (0, _blob.isBlobURL)(logoUrl)
  });

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    var siteEntities = select('core').getEditedEntityRecord('root', 'site');
    return _objectSpread({
      title: siteEntities.title
    }, (0, _lodash.pick)(getSettings(), ['imageSizes', 'isRTL', 'maxWidth']));
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
  (0, _element.createElement)("a", {
    href: siteUrl,
    className: classes,
    rel: "home",
    title: title,
    onClick: function onClick(event) {
      return event.preventDefault();
    }
  }, (0, _element.createElement)("span", {
    className: "custom-logo-link"
  }, (0, _element.createElement)("img", {
    className: "custom-logo",
    src: logoUrl,
    alt: alt,
    onLoad: function onLoad(event) {
      setNaturalSize((0, _lodash.pick)(event.target, ['naturalWidth', 'naturalHeight']));
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
    return (0, _element.createElement)("div", {
      style: {
        width: width,
        height: height
      }
    }, img);
  }

  var currentWidth = width || imageWidthWithinContainer;
  var ratio = naturalWidth / naturalHeight;
  var currentHeight = currentWidth / ratio;
  var minWidth = naturalWidth < naturalHeight ? _constants.MIN_SIZE : _constants.MIN_SIZE * ratio;
  var minHeight = naturalHeight < naturalWidth ? _constants.MIN_SIZE : _constants.MIN_SIZE / ratio; // With the current implementation of ResizableBox, an image needs an
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


  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Site Logo Settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Image width'),
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
  }))), (0, _element.createElement)(_components.ResizableBox, {
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

function LogoEdit(_ref2) {
  var attributes = _ref2.attributes,
      className = _ref2.className,
      setAttributes = _ref2.setAttributes,
      isSelected = _ref2.isSelected;
  var width = attributes.width;

  var _useState3 = (0, _element.useState)(),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      logoUrl = _useState4[0],
      setLogoUrl = _useState4[1];

  var _useState5 = (0, _element.useState)(),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      error = _useState6[0],
      setError = _useState6[1];

  var ref = (0, _element.useRef)();

  var _useSelect2 = (0, _data.useSelect)(function (select) {
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

  var _useDispatch2 = (0, _data.useDispatch)('core'),
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

  var controls = (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, logoUrl && (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
    mediaURL: logoUrl,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: ACCEPT_MEDIA_STRING,
    onSelect: onSelectLogo,
    onError: onUploadError
  }), !!logoUrl && (0, _element.createElement)(_components.ToolbarButton, {
    icon: _icons.trash,
    onClick: function onClick() {
      return deleteLogo();
    },
    label: (0, _i18n.__)('Delete Site Logo')
  })));
  var label = (0, _i18n.__)('Site Logo');
  var logoImage;

  if (sitelogo === undefined) {
    logoImage = (0, _element.createElement)(_components.Spinner, null);
  }

  if (!!logoUrl) {
    logoImage = (0, _element.createElement)(SiteLogo, {
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

  var mediaPlaceholder = (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
    icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
      icon: _icon.default
    }),
    labels: {
      title: label,
      instructions: (0, _i18n.__)('Upload an image, or pick one from your media library, to be your site logo')
    },
    onSelect: onSelectLogo,
    accept: ACCEPT_MEDIA_STRING,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    mediaPreview: logoImage,
    notices: error && (0, _element.createElement)(_components.Notice, {
      status: "error",
      isDismissible: false
    }, error),
    onError: onUploadError
  });
  var classes = (0, _classnames.default)(className, {
    'is-resized': !!width,
    'is-focused': isSelected
  });
  var key = !!logoUrl;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    ref: ref,
    className: classes,
    key: key
  });
  return (0, _element.createElement)("div", blockWrapperProps, controls, logoUrl && logoImage, !logoUrl && mediaPlaceholder);
}
//# sourceMappingURL=edit.js.map