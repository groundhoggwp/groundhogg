"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Gallery = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _galleryImage = _interopRequireDefault(require("./gallery-image"));

var _shared = require("./shared");

var _galleryStyles = _interopRequireDefault(require("./gallery-styles.scss"));

var _tiles = _interopRequireDefault(require("./tiles"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * WordPress dependencies
 */
var TILE_SPACING = 15; // we must limit displayed columns since readable content max-width is 580px

var MAX_DISPLAYED_COLUMNS = 4;
var MAX_DISPLAYED_COLUMNS_NARROW = 2;

var Gallery = function Gallery(props) {
  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isCaptionSelected = _useState2[0],
      setIsCaptionSelected = _useState2[1];

  (0, _element.useEffect)(_reactNativeBridge.mediaUploadSync, []);
  var isRTL = (0, _data.useSelect)(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);
  var clientId = props.clientId,
      selectedImage = props.selectedImage,
      mediaPlaceholder = props.mediaPlaceholder,
      onBlur = props.onBlur,
      onMoveBackward = props.onMoveBackward,
      onMoveForward = props.onMoveForward,
      onRemoveImage = props.onRemoveImage,
      onSelectImage = props.onSelectImage,
      onSetImageAttributes = props.onSetImageAttributes,
      onFocusGalleryCaption = props.onFocusGalleryCaption,
      attributes = props.attributes,
      isSelected = props.isSelected,
      isNarrow = props.isNarrow,
      onFocus = props.onFocus,
      insertBlocksAfter = props.insertBlocksAfter;
  var _attributes$columns = attributes.columns,
      columns = _attributes$columns === void 0 ? (0, _shared.defaultColumnsNumber)(attributes) : _attributes$columns,
      imageCrop = attributes.imageCrop,
      images = attributes.images; // limit displayed columns when isNarrow is true (i.e. when viewport width is
  // less than "small", where small = 600)

  var displayedColumns = isNarrow ? Math.min(columns, MAX_DISPLAYED_COLUMNS_NARROW) : Math.min(columns, MAX_DISPLAYED_COLUMNS);

  var selectImage = function selectImage(index) {
    return function () {
      if (isCaptionSelected) {
        setIsCaptionSelected(false);
      } // we need to fully invoke the curried function here


      onSelectImage(index)();
    };
  };

  var focusGalleryCaption = function focusGalleryCaption() {
    if (!isCaptionSelected) {
      setIsCaptionSelected(true);
    }

    onFocusGalleryCaption();
  };

  return (0, _element.createElement)(_reactNative.View, {
    style: {
      flex: 1
    }
  }, (0, _element.createElement)(_tiles.default, {
    columns: displayedColumns,
    spacing: TILE_SPACING,
    style: isSelected ? _galleryStyles.default.galleryTilesContainerSelected : undefined
  }, images.map(function (img, index) {
    var ariaLabel = (0, _i18n.sprintf)(
    /* translators: 1: the order number of the image. 2: the total number of images. */
    (0, _i18n.__)('image %1$d of %2$d in gallery'), index + 1, images.length);
    return (0, _element.createElement)(_galleryImage.default, {
      key: img.id || img.url,
      url: img.url,
      alt: img.alt,
      id: parseInt(img.id, 10) // make id an integer explicitly
      ,
      isCropped: imageCrop,
      isFirstItem: index === 0,
      isLastItem: index + 1 === images.length,
      isSelected: isSelected && selectedImage === index,
      isBlockSelected: isSelected,
      onMoveBackward: onMoveBackward(index),
      onMoveForward: onMoveForward(index),
      onRemove: onRemoveImage(index),
      onSelect: selectImage(index),
      onSelectBlock: onFocus,
      setAttributes: function setAttributes(attrs) {
        return onSetImageAttributes(index, attrs);
      },
      caption: img.caption,
      "aria-label": ariaLabel,
      isRTL: isRTL
    });
  })), mediaPlaceholder, (0, _element.createElement)(_blockEditor.BlockCaption, {
    clientId: clientId,
    isSelected: isCaptionSelected,
    accessible: true,
    accessibilityLabelCreator: function accessibilityLabelCreator(caption) {
      return (0, _lodash.isEmpty)(caption) ?
      /* translators: accessibility text. Empty gallery caption. */
      'Gallery caption. Empty' : (0, _i18n.sprintf)(
      /* translators: accessibility text. %s: gallery caption. */
      (0, _i18n.__)('Gallery caption. %s'), caption);
    },
    onFocus: focusGalleryCaption,
    onBlur: onBlur // always assign onBlur as props
    ,
    insertBlocksAfter: insertBlocksAfter
  }));
};

exports.Gallery = Gallery;
var _default = Gallery;
exports.default = _default;
//# sourceMappingURL=gallery.native.js.map