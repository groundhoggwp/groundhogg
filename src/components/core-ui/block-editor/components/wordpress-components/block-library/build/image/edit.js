"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ImageEdit = ImageEdit;
exports.default = exports.isExternalImage = exports.pickRelevantMediaFiles = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blob = require("@wordpress/blob");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _image = _interopRequireDefault(require("./image"));

var _constants = require("./constants");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var pickRelevantMediaFiles = function pickRelevantMediaFiles(image) {
  var imageProps = (0, _lodash.pick)(image, ['alt', 'id', 'link', 'caption']);
  imageProps.url = (0, _lodash.get)(image, ['sizes', 'large', 'url']) || (0, _lodash.get)(image, ['media_details', 'sizes', 'large', 'source_url']) || image.url;
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


exports.pickRelevantMediaFiles = pickRelevantMediaFiles;

var isTemporaryImage = function isTemporaryImage(id, url) {
  return !id && (0, _blob.isBlobURL)(url);
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


var isExternalImage = function isExternalImage(id, url) {
  return url && !id && !(0, _blob.isBlobURL)(url);
};

exports.isExternalImage = isExternalImage;

function ImageEdit(_ref) {
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
  var altRef = (0, _element.useRef)();
  (0, _element.useEffect)(function () {
    altRef.current = alt;
  }, [alt]);
  var captionRef = (0, _element.useRef)();
  (0, _element.useEffect)(function () {
    captionRef.current = caption;
  }, [caption]);
  var ref = (0, _element.useRef)();
  var mediaUpload = (0, _data.useSelect)(function (select) {
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
        mediaAttributes = (0, _lodash.omit)(mediaAttributes, ['alt']);
      }
    } // If a caption text was meanwhile written by the user,
    // make sure the text is not overwritten by empty captions.


    if (captionRef.current && !(0, _lodash.get)(mediaAttributes, ['caption'])) {
      mediaAttributes = (0, _lodash.omit)(mediaAttributes, ['caption']);
    }

    var additionalAttributes; // Reset the dimension attributes if changing to a different image.

    if (!media.id || media.id !== id) {
      additionalAttributes = {
        width: undefined,
        height: undefined,
        sizeSlug: _constants.DEFAULT_SIZE_SLUG
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
      switch (((_wp = wp) === null || _wp === void 0 ? void 0 : (_wp$media = _wp.media) === null || _wp$media === void 0 ? void 0 : (_wp$media$view = _wp$media.view) === null || _wp$media$view === void 0 ? void 0 : (_wp$media$view$settin = _wp$media$view.settings) === null || _wp$media$view$settin === void 0 ? void 0 : (_wp$media$view$settin2 = _wp$media$view$settin.defaultProps) === null || _wp$media$view$settin2 === void 0 ? void 0 : _wp$media$view$settin2.link) || _constants.LINK_DESTINATION_NONE) {
        case 'file':
        case _constants.LINK_DESTINATION_MEDIA:
          linkDestination = _constants.LINK_DESTINATION_MEDIA;
          break;

        case 'post':
        case _constants.LINK_DESTINATION_ATTACHMENT:
          linkDestination = _constants.LINK_DESTINATION_ATTACHMENT;
          break;

        case _constants.LINK_DESTINATION_CUSTOM:
          linkDestination = _constants.LINK_DESTINATION_CUSTOM;
          break;

        case _constants.LINK_DESTINATION_NONE:
          linkDestination = _constants.LINK_DESTINATION_NONE;
          break;
      }
    } // Check if the image is linked to it's media.


    var href;

    switch (linkDestination) {
      case _constants.LINK_DESTINATION_MEDIA:
        href = media.url;
        break;

      case _constants.LINK_DESTINATION_ATTACHMENT:
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
        sizeSlug: _constants.DEFAULT_SIZE_SLUG
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

  (0, _element.useEffect)(function () {
    if (!isTemp) {
      return;
    }

    var file = (0, _blob.getBlobByURL)(url);

    if (file) {
      mediaUpload({
        filesList: [file],
        onFileChange: function onFileChange(_ref2) {
          var _ref3 = (0, _slicedToArray2.default)(_ref2, 1),
              img = _ref3[0];

          onSelectImage(img);
        },
        allowedTypes: _constants.ALLOWED_MEDIA_TYPES,
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

  (0, _element.useEffect)(function () {
    if (!isTemp) {
      return;
    }

    return function () {
      (0, _blob.revokeBlobURL)(url);
    };
  }, [isTemp]);
  var isExternal = isExternalImage(id, url);
  var controls = (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.BlockAlignmentToolbar, {
    value: align,
    onChange: updateAlignment
  }));
  var src = isExternal ? url : undefined;
  var mediaPreview = !!url && (0, _element.createElement)("img", {
    alt: (0, _i18n.__)('Edit image'),
    title: (0, _i18n.__)('Edit image'),
    className: 'edit-image-preview',
    src: url
  });
  var mediaPlaceholder = (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
    icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
      icon: _icons.image
    }),
    onSelect: onSelectImage,
    onSelectURL: onSelectURL,
    notices: noticeUI,
    onError: onUploadError,
    accept: "image/*",
    allowedTypes: _constants.ALLOWED_MEDIA_TYPES,
    value: {
      id: id,
      src: src
    },
    mediaPreview: mediaPreview,
    disableMediaButtons: url
  });
  var classes = (0, _classnames2.default)(className, (0, _defineProperty2.default)({
    'is-transient': (0, _blob.isBlobURL)(url),
    'is-resized': !!width || !!height,
    'is-focused': isSelected
  }, "size-".concat(sizeSlug), sizeSlug));
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    ref: ref,
    className: classes
  });
  return (0, _element.createElement)(_element.Fragment, null, controls, (0, _element.createElement)("figure", blockWrapperProps, url && (0, _element.createElement)(_image.default, {
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

var _default = (0, _components.withNotices)(ImageEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map