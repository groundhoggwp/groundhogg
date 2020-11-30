"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Image;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _blob = require("@wordpress/blob");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _url = require("@wordpress/url");

var _blocks = require("@wordpress/blocks");

var _icons = require("@wordpress/icons");

var _util = require("../embed/util");

var _useClientWidth = _interopRequireDefault(require("./use-client-width"));

var _imageEditor = _interopRequireDefault(require("./image-editor"));

var _edit = require("./edit");

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
 * Module constants
 */
function getFilename(url) {
  var path = (0, _url.getPath)(url);

  if (path) {
    return (0, _lodash.last)(path.split('/'));
  }
}

function Image(_ref) {
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
  var captionRef = (0, _element.useRef)();
  var prevUrl = (0, _compose.usePrevious)(url);
  var image = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getMedia = _select.getMedia;

    return id && isSelected ? getMedia(id) : null;
  }, [id, isSelected]);

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select2 = select('core/block-editor'),
        getSettings = _select2.getSettings;

    return (0, _lodash.pick)(getSettings(), ['imageEditing', 'imageSizes', 'isRTL', 'maxWidth', 'mediaUpload']);
  }),
      imageEditing = _useSelect.imageEditing,
      imageSizes = _useSelect.imageSizes,
      isRTL = _useSelect.isRTL,
      maxWidth = _useSelect.maxWidth,
      mediaUpload = _useSelect.mediaUpload;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      toggleSelection = _useDispatch.toggleSelection;

  var _useDispatch2 = (0, _data.useDispatch)('core/notices'),
      createErrorNotice = _useDispatch2.createErrorNotice,
      createSuccessNotice = _useDispatch2.createSuccessNotice;

  var isLargeViewport = (0, _compose.useViewportMatch)('medium');

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      captionFocused = _useState2[0],
      setCaptionFocused = _useState2[1];

  var isWideAligned = (0, _lodash.includes)(['wide', 'full'], align);

  var _useState3 = (0, _element.useState)({}),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      _useState4$ = _useState4[0],
      naturalWidth = _useState4$.naturalWidth,
      naturalHeight = _useState4$.naturalHeight,
      setNaturalSize = _useState4[1];

  var _useState5 = (0, _element.useState)(false),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      isEditingImage = _useState6[0],
      setIsEditingImage = _useState6[1];

  var _useState7 = (0, _element.useState)(),
      _useState8 = (0, _slicedToArray2.default)(_useState7, 2),
      externalBlob = _useState8[0],
      setExternalBlob = _useState8[1];

  var clientWidth = (0, _useClientWidth.default)(containerRef, [align]);
  var isResizable = !isWideAligned && isLargeViewport;
  var imageSizeOptions = (0, _lodash.map)((0, _lodash.filter)(imageSizes, function (_ref2) {
    var slug = _ref2.slug;
    return (0, _lodash.get)(image, ['media_details', 'sizes', slug, 'source_url']);
  }), function (_ref3) {
    var name = _ref3.name,
        slug = _ref3.slug;
    return {
      value: slug,
      label: name
    };
  });
  (0, _element.useEffect)(function () {
    if (!isSelected) {
      setCaptionFocused(false);
    }
  }, [isSelected]); // If an image is externally hosted, try to fetch the image data. This may
  // fail if the image host doesn't allow CORS with the domain. If it works,
  // we can enable a button in the toolbar to upload the image.

  (0, _element.useEffect)(function () {
    if (!(0, _edit.isExternalImage)(id, url) || !isSelected || externalBlob) {
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

  (0, _element.useEffect)(function () {
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
    var embedBlock = (0, _util.createUpgradedEmbedBlock)({
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
    var newUrl = (0, _lodash.get)(image, ['media_details', 'sizes', newSizeSlug, 'source_url']);

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
        var _ref5 = (0, _slicedToArray2.default)(_ref4, 1),
            img = _ref5[0];

        onSelectImage(img);

        if ((0, _blob.isBlobURL)(img.url)) {
          return;
        }

        setExternalBlob();
        createSuccessNotice((0, _i18n.__)('Image uploaded.'), {
          type: 'snackbar'
        });
      },
      allowedTypes: _constants.ALLOWED_MEDIA_TYPES,
      onError: function onError(message) {
        createErrorNotice(message, {
          type: 'snackbar'
        });
      }
    });
  }

  (0, _element.useEffect)(function () {
    if (!isSelected) {
      setIsEditingImage(false);
    }
  }, [isSelected]);
  var canEditImage = id && naturalWidth && naturalHeight && imageEditing;
  var controls = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, !isEditingImage && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_blockEditor.__experimentalImageURLInputUI, {
    url: href || '',
    onChangeUrl: onSetHref,
    linkDestination: linkDestination,
    mediaUrl: image && image.source_url,
    mediaLink: image && image.link,
    linkTarget: linkTarget,
    linkClass: linkClass,
    rel: rel
  })), canEditImage && !isEditingImage && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    onClick: function onClick() {
      return setIsEditingImage(true);
    },
    icon: _icons.crop,
    label: (0, _i18n.__)('Crop')
  })), externalBlob && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    onClick: uploadExternal,
    icon: _icons.upload,
    label: (0, _i18n.__)('Upload external image')
  })), !isEditingImage && (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
    mediaId: id,
    mediaURL: url,
    allowedTypes: _constants.ALLOWED_MEDIA_TYPES,
    accept: "image/*",
    onSelect: onSelectImage,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Image settings')
  }, (0, _element.createElement)(_components.TextareaControl, {
    label: (0, _i18n.__)('Alt text (alternative text)'),
    value: alt,
    onChange: updateAlt,
    help: (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ExternalLink, {
      href: "https://www.w3.org/WAI/tutorials/images/decision-tree"
    }, (0, _i18n.__)('Describe the purpose of the image')), (0, _i18n.__)('Leave empty if the image is purely decorative.'))
  }), (0, _element.createElement)(_blockEditor.__experimentalImageSizeControl, {
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
  }))), (0, _element.createElement)(_blockEditor.InspectorAdvancedControls, null, (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Title attribute'),
    value: title || '',
    onChange: onSetTitle,
    help: (0, _element.createElement)(_element.Fragment, null, (0, _i18n.__)('Describe the role of this image on the page.'), (0, _element.createElement)(_components.ExternalLink, {
      href: "https://www.w3.org/TR/html52/dom.html#the-title-attribute"
    }, (0, _i18n.__)('(Note: many devices and browsers do not display this text.)')))
  })));
  var filename = getFilename(url);
  var defaultedAlt;

  if (alt) {
    defaultedAlt = alt;
  } else if (filename) {
    defaultedAlt = (0, _i18n.sprintf)(
    /* translators: %s: file name */
    (0, _i18n.__)('This image has an empty alt attribute; its file name is %s'), filename);
  } else {
    defaultedAlt = (0, _i18n.__)('This image has an empty alt attribute');
  }

  var img = // Disable reason: Image itself is not meant to be interactive, but
  // should direct focus to block.

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("img", {
    src: url,
    alt: defaultedAlt,
    onClick: onImageClick,
    onError: function onError() {
      return onImageError();
    },
    onLoad: function onLoad(event) {
      setNaturalSize((0, _lodash.pick)(event.target, ['naturalWidth', 'naturalHeight']));
    }
  }), (0, _blob.isBlobURL)(url) && (0, _element.createElement)(_components.Spinner, null))
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
    img = (0, _element.createElement)(_imageEditor.default, {
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
    img = (0, _element.createElement)("div", {
      style: {
        width: width,
        height: height
      }
    }, img);
  } else {
    var currentWidth = width || imageWidthWithinContainer;
    var currentHeight = height || imageHeightWithinContainer;

    var _ratio = naturalWidth / naturalHeight;

    var minWidth = naturalWidth < naturalHeight ? _constants.MIN_SIZE : _constants.MIN_SIZE * _ratio;
    var minHeight = naturalHeight < naturalWidth ? _constants.MIN_SIZE : _constants.MIN_SIZE / _ratio; // With the current implementation of ResizableBox, an image needs an
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


    img = (0, _element.createElement)(_components.ResizableBox, {
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

  return (0, _element.createElement)(_element.Fragment, null, controls, img, (!_blockEditor.RichText.isEmpty(caption) || isSelected) && (0, _element.createElement)(_blockEditor.RichText, {
    ref: captionRef,
    tagName: "figcaption",
    placeholder: (0, _i18n.__)('Write caption…'),
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
      return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
    }
  }));
}
//# sourceMappingURL=image.js.map