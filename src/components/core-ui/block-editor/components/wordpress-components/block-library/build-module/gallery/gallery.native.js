import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
import { isEmpty } from 'lodash';
/**
 * Internal dependencies
 */

import GalleryImage from './gallery-image';
import { defaultColumnsNumber } from './shared';
import styles from './gallery-styles.scss';
import Tiles from './tiles';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { BlockCaption } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { mediaUploadSync } from '@wordpress/react-native-bridge';
import { useSelect } from '@wordpress/data';
var TILE_SPACING = 15; // we must limit displayed columns since readable content max-width is 580px

var MAX_DISPLAYED_COLUMNS = 4;
var MAX_DISPLAYED_COLUMNS_NARROW = 2;
export var Gallery = function Gallery(props) {
  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isCaptionSelected = _useState2[0],
      setIsCaptionSelected = _useState2[1];

  useEffect(mediaUploadSync, []);
  var isRTL = useSelect(function (select) {
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
      columns = _attributes$columns === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns,
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

  return createElement(View, {
    style: {
      flex: 1
    }
  }, createElement(Tiles, {
    columns: displayedColumns,
    spacing: TILE_SPACING,
    style: isSelected ? styles.galleryTilesContainerSelected : undefined
  }, images.map(function (img, index) {
    var ariaLabel = sprintf(
    /* translators: 1: the order number of the image. 2: the total number of images. */
    __('image %1$d of %2$d in gallery'), index + 1, images.length);
    return createElement(GalleryImage, {
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
  })), mediaPlaceholder, createElement(BlockCaption, {
    clientId: clientId,
    isSelected: isCaptionSelected,
    accessible: true,
    accessibilityLabelCreator: function accessibilityLabelCreator(caption) {
      return isEmpty(caption) ?
      /* translators: accessibility text. Empty gallery caption. */
      'Gallery caption. Empty' : sprintf(
      /* translators: accessibility text. %s: gallery caption. */
      __('Gallery caption. %s'), caption);
    },
    onFocus: focusGalleryCaption,
    onBlur: onBlur // always assign onBlur as props
    ,
    insertBlocksAfter: insertBlocksAfter
  }));
};
export default Gallery;
//# sourceMappingURL=gallery.native.js.map