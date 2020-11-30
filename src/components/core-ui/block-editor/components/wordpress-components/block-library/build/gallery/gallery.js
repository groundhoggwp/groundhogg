"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Gallery = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _galleryImage = _interopRequireDefault(require("./gallery-image"));

var _shared = require("./shared");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var Gallery = function Gallery(props) {
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
      columns = _attributes$columns === void 0 ? (0, _shared.defaultColumnsNumber)(attributes) : _attributes$columns,
      caption = attributes.caption,
      imageCrop = attributes.imageCrop,
      images = attributes.images;
  return (0, _element.createElement)("figure", {
    className: (0, _classnames2.default)(className, (_classnames = {}, (0, _defineProperty2.default)(_classnames, "align".concat(align), align), (0, _defineProperty2.default)(_classnames, "columns-".concat(columns), columns), (0, _defineProperty2.default)(_classnames, 'is-cropped', imageCrop), _classnames))
  }, (0, _element.createElement)("ul", {
    className: "blocks-gallery-grid"
  }, images.map(function (img, index) {
    var ariaLabel = (0, _i18n.sprintf)(
    /* translators: 1: the order number of the image. 2: the total number of images. */
    (0, _i18n.__)('image %1$d of %2$d in gallery'), index + 1, images.length);
    return (0, _element.createElement)("li", {
      className: "blocks-gallery-item",
      key: img.id || img.url
    }, (0, _element.createElement)(_galleryImage.default, {
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
  })), mediaPlaceholder, (0, _element.createElement)(RichTextVisibilityHelper, {
    isHidden: !isSelected && _blockEditor.RichText.isEmpty(caption),
    tagName: "figcaption",
    className: "blocks-gallery-caption",
    placeholder: (0, _i18n.__)('Write gallery caption…'),
    value: caption,
    unstableOnFocus: onFocusGalleryCaption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    inlineToolbar: true,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
    }
  }));
};

exports.Gallery = Gallery;

function RichTextVisibilityHelper(_ref) {
  var isHidden = _ref.isHidden,
      richTextProps = (0, _objectWithoutProperties2.default)(_ref, ["isHidden"]);
  return isHidden ? (0, _element.createElement)(_components.VisuallyHidden, (0, _extends2.default)({
    as: _blockEditor.RichText
  }, richTextProps)) : (0, _element.createElement)(_blockEditor.RichText, richTextProps);
}

var _default = Gallery;
exports.default = _default;
//# sourceMappingURL=gallery.js.map