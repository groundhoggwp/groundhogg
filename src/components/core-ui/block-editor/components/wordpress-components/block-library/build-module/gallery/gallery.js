import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
import { VisuallyHidden } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import GalleryImage from './gallery-image';
import { defaultColumnsNumber } from './shared';
export var Gallery = function Gallery(props) {
  var _classnames;

  var attributes = props.attributes,
      className = props.className,
      isSelected = props.isSelected,
      setAttributes = props.setAttributes,
      selectedImage = props.selectedImage,
      mediaPlaceholder = props.mediaPlaceholder,
      onMoveBackward = props.onMoveBackward,
      onMoveForward = props.onMoveForward,
      onRemoveImage = props.onRemoveImage,
      onSelectImage = props.onSelectImage,
      onDeselectImage = props.onDeselectImage,
      onSetImageAttributes = props.onSetImageAttributes,
      onFocusGalleryCaption = props.onFocusGalleryCaption,
      insertBlocksAfter = props.insertBlocksAfter;
  var align = attributes.align,
      _attributes$columns = attributes.columns,
      columns = _attributes$columns === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns,
      caption = attributes.caption,
      imageCrop = attributes.imageCrop,
      images = attributes.images;
  return createElement("figure", {
    className: classnames(className, (_classnames = {}, _defineProperty(_classnames, "align".concat(align), align), _defineProperty(_classnames, "columns-".concat(columns), columns), _defineProperty(_classnames, 'is-cropped', imageCrop), _classnames))
  }, createElement("ul", {
    className: "blocks-gallery-grid"
  }, images.map(function (img, index) {
    var ariaLabel = sprintf(
    /* translators: 1: the order number of the image. 2: the total number of images. */
    __('image %1$d of %2$d in gallery'), index + 1, images.length);
    return createElement("li", {
      className: "blocks-gallery-item",
      key: img.id || img.url
    }, createElement(GalleryImage, {
      url: img.url,
      alt: img.alt,
      id: img.id,
      isFirstItem: index === 0,
      isLastItem: index + 1 === images.length,
      isSelected: isSelected && selectedImage === index,
      onMoveBackward: onMoveBackward(index),
      onMoveForward: onMoveForward(index),
      onRemove: onRemoveImage(index),
      onSelect: onSelectImage(index),
      onDeselect: onDeselectImage(index),
      setAttributes: function setAttributes(attrs) {
        return onSetImageAttributes(index, attrs);
      },
      caption: img.caption,
      "aria-label": ariaLabel,
      sizeSlug: attributes.sizeSlug
    }));
  })), mediaPlaceholder, createElement(RichTextVisibilityHelper, {
    isHidden: !isSelected && RichText.isEmpty(caption),
    tagName: "figcaption",
    className: "blocks-gallery-caption",
    placeholder: __('Write gallery caption…'),
    value: caption,
    unstableOnFocus: onFocusGalleryCaption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    inlineToolbar: true,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter(createBlock('core/paragraph'));
    }
  }));
};

function RichTextVisibilityHelper(_ref) {
  var isHidden = _ref.isHidden,
      richTextProps = _objectWithoutProperties(_ref, ["isHidden"]);

  return isHidden ? createElement(VisuallyHidden, _extends({
    as: RichText
  }, richTextProps)) : createElement(RichText, richTextProps);
}

export default Gallery;
//# sourceMappingURL=gallery.js.map