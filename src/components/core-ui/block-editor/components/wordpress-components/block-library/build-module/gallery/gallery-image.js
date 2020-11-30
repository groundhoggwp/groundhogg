import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { get, omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { Button, Spinner, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BACKSPACE, DELETE } from '@wordpress/keycodes';
import { withSelect, withDispatch } from '@wordpress/data';
import { RichText, MediaPlaceholder } from '@wordpress/block-editor';
import { isBlobURL } from '@wordpress/blob';
import { compose } from '@wordpress/compose';
import { closeSmall, chevronLeft, chevronRight, edit, image as imageIcon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { pickRelevantMediaFiles } from './shared';
import { LINK_DESTINATION_ATTACHMENT, LINK_DESTINATION_MEDIA } from './constants';

var isTemporaryImage = function isTemporaryImage(id, url) {
  return !id && isBlobURL(url);
};

var GalleryImage = /*#__PURE__*/function (_Component) {
  _inherits(GalleryImage, _Component);

  var _super = _createSuper(GalleryImage);

  function GalleryImage() {
    var _this;

    _classCallCheck(this, GalleryImage);

    _this = _super.apply(this, arguments);
    _this.onSelectImage = _this.onSelectImage.bind(_assertThisInitialized(_this));
    _this.onSelectCaption = _this.onSelectCaption.bind(_assertThisInitialized(_this));
    _this.onRemoveImage = _this.onRemoveImage.bind(_assertThisInitialized(_this));
    _this.bindContainer = _this.bindContainer.bind(_assertThisInitialized(_this));
    _this.onEdit = _this.onEdit.bind(_assertThisInitialized(_this));
    _this.onSelectImageFromLibrary = _this.onSelectImageFromLibrary.bind(_assertThisInitialized(_this));
    _this.onSelectCustomURL = _this.onSelectCustomURL.bind(_assertThisInitialized(_this));
    _this.state = {
      captionSelected: false,
      isEditing: false
    };
    return _this;
  }

  _createClass(GalleryImage, [{
    key: "bindContainer",
    value: function bindContainer(ref) {
      this.container = ref;
    }
  }, {
    key: "onSelectCaption",
    value: function onSelectCaption() {
      if (!this.state.captionSelected) {
        this.setState({
          captionSelected: true
        });
      }

      if (!this.props.isSelected) {
        this.props.onSelect();
      }
    }
  }, {
    key: "onSelectImage",
    value: function onSelectImage() {
      if (!this.props.isSelected) {
        this.props.onSelect();
      }

      if (this.state.captionSelected) {
        this.setState({
          captionSelected: false
        });
      }
    }
  }, {
    key: "onRemoveImage",
    value: function onRemoveImage(event) {
      if (this.container === document.activeElement && this.props.isSelected && [BACKSPACE, DELETE].indexOf(event.keyCode) !== -1) {
        event.stopPropagation();
        event.preventDefault();
        this.props.onRemove();
      }
    }
  }, {
    key: "onEdit",
    value: function onEdit() {
      this.setState({
        isEditing: true
      });
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          isSelected = _this$props.isSelected,
          image = _this$props.image,
          url = _this$props.url,
          __unstableMarkNextChangeAsNotPersistent = _this$props.__unstableMarkNextChangeAsNotPersistent;

      if (image && !url) {
        __unstableMarkNextChangeAsNotPersistent();

        this.props.setAttributes({
          url: image.source_url,
          alt: image.alt_text
        });
      } // unselect the caption so when the user selects other image and comeback
      // the caption is not immediately selected


      if (this.state.captionSelected && !isSelected && prevProps.isSelected) {
        this.setState({
          captionSelected: false
        });
      }
    }
  }, {
    key: "deselectOnBlur",
    value: function deselectOnBlur() {
      this.props.onDeselect();
    }
  }, {
    key: "onSelectImageFromLibrary",
    value: function onSelectImageFromLibrary(media) {
      var _this$props2 = this.props,
          setAttributes = _this$props2.setAttributes,
          id = _this$props2.id,
          url = _this$props2.url,
          alt = _this$props2.alt,
          caption = _this$props2.caption,
          sizeSlug = _this$props2.sizeSlug;

      if (!media || !media.url) {
        return;
      }

      var mediaAttributes = pickRelevantMediaFiles(media, sizeSlug); // If the current image is temporary but an alt text was meanwhile
      // written by the user, make sure the text is not overwritten.

      if (isTemporaryImage(id, url)) {
        if (alt) {
          mediaAttributes = omit(mediaAttributes, ['alt']);
        }
      } // If a caption text was meanwhile written by the user,
      // make sure the text is not overwritten by empty captions.


      if (caption && !get(mediaAttributes, ['caption'])) {
        mediaAttributes = omit(mediaAttributes, ['caption']);
      }

      setAttributes(mediaAttributes);
      this.setState({
        isEditing: false
      });
    }
  }, {
    key: "onSelectCustomURL",
    value: function onSelectCustomURL(newURL) {
      var _this$props3 = this.props,
          setAttributes = _this$props3.setAttributes,
          url = _this$props3.url;

      if (newURL !== url) {
        setAttributes({
          url: newURL,
          id: undefined
        });
        this.setState({
          isEditing: false
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          url = _this$props4.url,
          alt = _this$props4.alt,
          id = _this$props4.id,
          linkTo = _this$props4.linkTo,
          link = _this$props4.link,
          isFirstItem = _this$props4.isFirstItem,
          isLastItem = _this$props4.isLastItem,
          isSelected = _this$props4.isSelected,
          caption = _this$props4.caption,
          onRemove = _this$props4.onRemove,
          onMoveForward = _this$props4.onMoveForward,
          onMoveBackward = _this$props4.onMoveBackward,
          setAttributes = _this$props4.setAttributes,
          ariaLabel = _this$props4['aria-label'];
      var isEditing = this.state.isEditing;
      var href;

      switch (linkTo) {
        case LINK_DESTINATION_MEDIA:
          href = url;
          break;

        case LINK_DESTINATION_ATTACHMENT:
          href = link;
          break;
      }

      var img = // Disable reason: Image itself is not meant to be interactive, but should
      // direct image selection and unfocus caption fields.

      /* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
      createElement(Fragment, null, createElement("img", {
        src: url,
        alt: alt,
        "data-id": id,
        onClick: this.onSelectImage,
        onFocus: this.onSelectImage,
        onKeyDown: this.onRemoveImage,
        tabIndex: "0",
        "aria-label": ariaLabel,
        ref: this.bindContainer
      }), isBlobURL(url) && createElement(Spinner, null))
      /* eslint-enable jsx-a11y/no-noninteractive-element-interactions */
      ;
      var className = classnames({
        'is-selected': isSelected,
        'is-transient': isBlobURL(url)
      });
      return createElement("figure", {
        className: className
      }, !isEditing && (href ? createElement("a", {
        href: href
      }, img) : img), isEditing && createElement(MediaPlaceholder, {
        labels: {
          title: __('Edit gallery image')
        },
        icon: imageIcon,
        onSelect: this.onSelectImageFromLibrary,
        onSelectURL: this.onSelectCustomURL,
        accept: "image/*",
        allowedTypes: ['image'],
        value: {
          id: id,
          src: url
        }
      }), createElement(ButtonGroup, {
        className: "block-library-gallery-item__inline-menu is-left"
      }, createElement(Button, {
        icon: chevronLeft,
        onClick: isFirstItem ? undefined : onMoveBackward,
        label: __('Move image backward'),
        "aria-disabled": isFirstItem,
        disabled: !isSelected
      }), createElement(Button, {
        icon: chevronRight,
        onClick: isLastItem ? undefined : onMoveForward,
        label: __('Move image forward'),
        "aria-disabled": isLastItem,
        disabled: !isSelected
      })), createElement(ButtonGroup, {
        className: "block-library-gallery-item__inline-menu is-right"
      }, createElement(Button, {
        icon: edit,
        onClick: this.onEdit,
        label: __('Replace image'),
        disabled: !isSelected
      }), createElement(Button, {
        icon: closeSmall,
        onClick: onRemove,
        label: __('Remove image'),
        disabled: !isSelected
      })), !isEditing && (isSelected || caption) && createElement(RichText, {
        tagName: "figcaption",
        placeholder: isSelected ? __('Write caption…') : null,
        value: caption,
        isSelected: this.state.captionSelected,
        onChange: function onChange(newCaption) {
          return setAttributes({
            caption: newCaption
          });
        },
        unstableOnFocus: this.onSelectCaption,
        inlineToolbar: true
      }));
    }
  }]);

  return GalleryImage;
}(Component);

export default compose([withSelect(function (select, ownProps) {
  var _select = select('core'),
      getMedia = _select.getMedia;

  var id = ownProps.id;
  return {
    image: id ? getMedia(parseInt(id, 10)) : null
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      __unstableMarkNextChangeAsNotPersistent = _dispatch.__unstableMarkNextChangeAsNotPersistent;

  return {
    __unstableMarkNextChangeAsNotPersistent: __unstableMarkNextChangeAsNotPersistent
  };
})])(GalleryImage);
//# sourceMappingURL=gallery-image.js.map