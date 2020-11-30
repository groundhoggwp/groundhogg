"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _blob = require("@wordpress/blob");

var _data = require("@wordpress/data");

var _viewport = require("@wordpress/viewport");

var _sharedIcon = require("./shared-icon");

var _shared = require("./shared");

var _gallery = _interopRequireDefault(require("./gallery"));

var _constants = require("./constants");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MAX_COLUMNS = 8;
var linkOptions = [{
  value: _constants.LINK_DESTINATION_ATTACHMENT,
  label: (0, _i18n.__)('Attachment Page')
}, {
  value: _constants.LINK_DESTINATION_MEDIA,
  label: (0, _i18n.__)('Media File')
}, {
  value: _constants.LINK_DESTINATION_NONE,
  label: (0, _i18n.__)('None')
}];
var ALLOWED_MEDIA_TYPES = ['image'];

var PLACEHOLDER_TEXT = _element.Platform.select({
  web: (0, _i18n.__)('Drag images, upload new ones or select files from your library.'),
  native: (0, _i18n.__)('ADD MEDIA')
});

var MOBILE_CONTROL_PROPS_RANGE_CONTROL = _element.Platform.select({
  web: {},
  native: {
    type: 'stepper'
  }
});

var GalleryEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(GalleryEdit, _Component);

  var _super = _createSuper(GalleryEdit);

  function GalleryEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, GalleryEdit);
    _this = _super.apply(this, arguments);
    _this.onSelectImage = _this.onSelectImage.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSelectImages = _this.onSelectImages.bind((0, _assertThisInitialized2.default)(_this));
    _this.onDeselectImage = _this.onDeselectImage.bind((0, _assertThisInitialized2.default)(_this));
    _this.setLinkTo = _this.setLinkTo.bind((0, _assertThisInitialized2.default)(_this));
    _this.setColumnsNumber = _this.setColumnsNumber.bind((0, _assertThisInitialized2.default)(_this));
    _this.toggleImageCrop = _this.toggleImageCrop.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMove = _this.onMove.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMoveForward = _this.onMoveForward.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMoveBackward = _this.onMoveBackward.bind((0, _assertThisInitialized2.default)(_this));
    _this.onRemoveImage = _this.onRemoveImage.bind((0, _assertThisInitialized2.default)(_this));
    _this.onUploadError = _this.onUploadError.bind((0, _assertThisInitialized2.default)(_this));
    _this.setImageAttributes = _this.setImageAttributes.bind((0, _assertThisInitialized2.default)(_this));
    _this.setAttributes = _this.setAttributes.bind((0, _assertThisInitialized2.default)(_this));
    _this.onFocusGalleryCaption = _this.onFocusGalleryCaption.bind((0, _assertThisInitialized2.default)(_this));
    _this.getImagesSizeOptions = _this.getImagesSizeOptions.bind((0, _assertThisInitialized2.default)(_this));
    _this.updateImagesSize = _this.updateImagesSize.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      selectedImage: null,
      attachmentCaptions: null
    };
    return _this;
  }

  (0, _createClass2.default)(GalleryEdit, [{
    key: "setAttributes",
    value: function setAttributes(attributes) {
      if (attributes.ids) {
        throw new Error('The "ids" attribute should not be changed directly. It is managed automatically when "images" attribute changes');
      }

      if (attributes.images) {
        attributes = _objectSpread(_objectSpread({}, attributes), {}, {
          // Unlike images[ n ].id which is a string, always ensure the
          // ids array contains numbers as per its attribute type.
          ids: (0, _lodash.map)(attributes.images, function (_ref) {
            var id = _ref.id;
            return parseInt(id, 10);
          })
        });
      }

      this.props.setAttributes(attributes);
    }
  }, {
    key: "onSelectImage",
    value: function onSelectImage(index) {
      var _this2 = this;

      return function () {
        if (_this2.state.selectedImage !== index) {
          _this2.setState({
            selectedImage: index
          });
        }
      };
    }
  }, {
    key: "onDeselectImage",
    value: function onDeselectImage(index) {
      var _this3 = this;

      return function () {
        if (_this3.state.selectedImage === index) {
          _this3.setState({
            selectedImage: null
          });
        }
      };
    }
  }, {
    key: "onMove",
    value: function onMove(oldIndex, newIndex) {
      var images = (0, _toConsumableArray2.default)(this.props.attributes.images);
      images.splice(newIndex, 1, this.props.attributes.images[oldIndex]);
      images.splice(oldIndex, 1, this.props.attributes.images[newIndex]);
      this.setState({
        selectedImage: newIndex
      });
      this.setAttributes({
        images: images
      });
    }
  }, {
    key: "onMoveForward",
    value: function onMoveForward(oldIndex) {
      var _this4 = this;

      return function () {
        if (oldIndex === _this4.props.attributes.images.length - 1) {
          return;
        }

        _this4.onMove(oldIndex, oldIndex + 1);
      };
    }
  }, {
    key: "onMoveBackward",
    value: function onMoveBackward(oldIndex) {
      var _this5 = this;

      return function () {
        if (oldIndex === 0) {
          return;
        }

        _this5.onMove(oldIndex, oldIndex - 1);
      };
    }
  }, {
    key: "onRemoveImage",
    value: function onRemoveImage(index) {
      var _this6 = this;

      return function () {
        var images = (0, _lodash.filter)(_this6.props.attributes.images, function (img, i) {
          return index !== i;
        });
        var columns = _this6.props.attributes.columns;

        _this6.setState({
          selectedImage: null
        });

        _this6.setAttributes({
          images: images,
          columns: columns ? Math.min(images.length, columns) : columns
        });
      };
    }
  }, {
    key: "selectCaption",
    value: function selectCaption(newImage, images, attachmentCaptions) {
      // The image id in both the images and attachmentCaptions arrays is a
      // string, so ensure comparison works correctly by converting the
      // newImage.id to a string.
      var newImageId = (0, _lodash.toString)(newImage.id);
      var currentImage = (0, _lodash.find)(images, {
        id: newImageId
      });
      var currentImageCaption = currentImage ? currentImage.caption : newImage.caption;

      if (!attachmentCaptions) {
        return currentImageCaption;
      }

      var attachment = (0, _lodash.find)(attachmentCaptions, {
        id: newImageId
      }); // if the attachment caption is updated

      if (attachment && attachment.caption !== newImage.caption) {
        return newImage.caption;
      }

      return currentImageCaption;
    }
  }, {
    key: "onSelectImages",
    value: function onSelectImages(newImages) {
      var _this7 = this;

      var _this$props$attribute = this.props.attributes,
          columns = _this$props$attribute.columns,
          images = _this$props$attribute.images,
          sizeSlug = _this$props$attribute.sizeSlug;
      var attachmentCaptions = this.state.attachmentCaptions;
      this.setState({
        attachmentCaptions: newImages.map(function (newImage) {
          return {
            // Store the attachmentCaption id as a string for consistency
            // with the type of the id in the images attribute.
            id: (0, _lodash.toString)(newImage.id),
            caption: newImage.caption
          };
        })
      });
      this.setAttributes({
        images: newImages.map(function (newImage) {
          return _objectSpread(_objectSpread({}, (0, _shared.pickRelevantMediaFiles)(newImage, sizeSlug)), {}, {
            caption: _this7.selectCaption(newImage, images, attachmentCaptions),
            // The id value is stored in a data attribute, so when the
            // block is parsed it's converted to a string. Converting
            // to a string here ensures it's type is consistent.
            id: (0, _lodash.toString)(newImage.id)
          });
        }),
        columns: columns ? Math.min(newImages.length, columns) : columns
      });
    }
  }, {
    key: "onUploadError",
    value: function onUploadError(message) {
      var noticeOperations = this.props.noticeOperations;
      noticeOperations.removeAllNotices();
      noticeOperations.createErrorNotice(message);
    }
  }, {
    key: "setLinkTo",
    value: function setLinkTo(value) {
      this.setAttributes({
        linkTo: value
      });
    }
  }, {
    key: "setColumnsNumber",
    value: function setColumnsNumber(value) {
      this.setAttributes({
        columns: value
      });
    }
  }, {
    key: "toggleImageCrop",
    value: function toggleImageCrop() {
      this.setAttributes({
        imageCrop: !this.props.attributes.imageCrop
      });
    }
  }, {
    key: "getImageCropHelp",
    value: function getImageCropHelp(checked) {
      return checked ? (0, _i18n.__)('Thumbnails are cropped to align.') : (0, _i18n.__)('Thumbnails are not cropped.');
    }
  }, {
    key: "onFocusGalleryCaption",
    value: function onFocusGalleryCaption() {
      this.setState({
        selectedImage: null
      });
    }
  }, {
    key: "setImageAttributes",
    value: function setImageAttributes(index, attributes) {
      var images = this.props.attributes.images;
      var setAttributes = this.setAttributes;

      if (!images[index]) {
        return;
      }

      setAttributes({
        images: [].concat((0, _toConsumableArray2.default)(images.slice(0, index)), [_objectSpread(_objectSpread({}, images[index]), attributes)], (0, _toConsumableArray2.default)(images.slice(index + 1)))
      });
    }
  }, {
    key: "getImagesSizeOptions",
    value: function getImagesSizeOptions() {
      var _this$props = this.props,
          imageSizes = _this$props.imageSizes,
          resizedImages = _this$props.resizedImages;
      return (0, _lodash.map)((0, _lodash.filter)(imageSizes, function (_ref2) {
        var slug = _ref2.slug;
        return (0, _lodash.some)(resizedImages, function (sizes) {
          return sizes[slug];
        });
      }), function (_ref3) {
        var name = _ref3.name,
            slug = _ref3.slug;
        return {
          value: slug,
          label: name
        };
      });
    }
  }, {
    key: "updateImagesSize",
    value: function updateImagesSize(sizeSlug) {
      var _this$props2 = this.props,
          images = _this$props2.attributes.images,
          resizedImages = _this$props2.resizedImages;
      var updatedImages = (0, _lodash.map)(images, function (image) {
        if (!image.id) {
          return image;
        }

        var url = (0, _lodash.get)(resizedImages, [parseInt(image.id, 10), sizeSlug]);
        return _objectSpread(_objectSpread({}, image), url && {
          url: url
        });
      });
      this.setAttributes({
        images: updatedImages,
        sizeSlug: sizeSlug
      });
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props3 = this.props,
          attributes = _this$props3.attributes,
          mediaUpload = _this$props3.mediaUpload;
      var images = attributes.images;

      if (_element.Platform.OS === 'web' && images && images.length > 0 && (0, _lodash.every)(images, function (_ref4) {
        var url = _ref4.url;
        return (0, _blob.isBlobURL)(url);
      })) {
        var filesList = (0, _lodash.map)(images, function (_ref5) {
          var url = _ref5.url;
          return (0, _blob.getBlobByURL)(url);
        });
        (0, _lodash.forEach)(images, function (_ref6) {
          var url = _ref6.url;
          return (0, _blob.revokeBlobURL)(url);
        });
        mediaUpload({
          filesList: filesList,
          onFileChange: this.onSelectImages,
          allowedTypes: ['image']
        });
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      // Deselect images when deselecting the block
      if (!this.props.isSelected && prevProps.isSelected) {
        this.setState({
          selectedImage: null,
          captionSelected: false
        });
      } // linkTo attribute must be saved so blocks don't break when changing image_default_link_type in options.php


      if (!this.props.attributes.linkTo) {
        var _window, _window$wp, _window$wp$media, _window$wp$media$view, _window$wp$media$view2, _window$wp$media$view3;

        this.setAttributes({
          linkTo: ((_window = window) === null || _window === void 0 ? void 0 : (_window$wp = _window.wp) === null || _window$wp === void 0 ? void 0 : (_window$wp$media = _window$wp.media) === null || _window$wp$media === void 0 ? void 0 : (_window$wp$media$view = _window$wp$media.view) === null || _window$wp$media$view === void 0 ? void 0 : (_window$wp$media$view2 = _window$wp$media$view.settings) === null || _window$wp$media$view2 === void 0 ? void 0 : (_window$wp$media$view3 = _window$wp$media$view2.defaultProps) === null || _window$wp$media$view3 === void 0 ? void 0 : _window$wp$media$view3.link) || _constants.LINK_DESTINATION_NONE
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          attributes = _this$props4.attributes,
          className = _this$props4.className,
          isSelected = _this$props4.isSelected,
          noticeUI = _this$props4.noticeUI,
          insertBlocksAfter = _this$props4.insertBlocksAfter;
      var _attributes$columns = attributes.columns,
          columns = _attributes$columns === void 0 ? (0, _shared.defaultColumnsNumber)(attributes) : _attributes$columns,
          imageCrop = attributes.imageCrop,
          images = attributes.images,
          linkTo = attributes.linkTo,
          sizeSlug = attributes.sizeSlug;
      var hasImages = !!images.length;
      var mediaPlaceholder = (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
        addToGallery: hasImages,
        isAppender: hasImages,
        className: className,
        disableMediaButtons: hasImages && !isSelected,
        icon: !hasImages && _sharedIcon.sharedIcon,
        labels: {
          title: !hasImages && (0, _i18n.__)('Gallery'),
          instructions: !hasImages && PLACEHOLDER_TEXT
        },
        onSelect: this.onSelectImages,
        accept: "image/*",
        allowedTypes: ALLOWED_MEDIA_TYPES,
        multiple: true,
        value: images,
        onError: this.onUploadError,
        notices: hasImages ? undefined : noticeUI,
        onFocus: this.props.onFocus
      });

      if (!hasImages) {
        return mediaPlaceholder;
      }

      var imageSizeOptions = this.getImagesSizeOptions();
      var shouldShowSizeOptions = hasImages && !(0, _lodash.isEmpty)(imageSizeOptions);
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Gallery settings')
      }, images.length > 1 && (0, _element.createElement)(_components.RangeControl, (0, _extends2.default)({
        label: (0, _i18n.__)('Columns'),
        value: columns,
        onChange: this.setColumnsNumber,
        min: 1,
        max: Math.min(MAX_COLUMNS, images.length)
      }, MOBILE_CONTROL_PROPS_RANGE_CONTROL, {
        required: true
      })), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Crop images'),
        checked: !!imageCrop,
        onChange: this.toggleImageCrop,
        help: this.getImageCropHelp
      }), (0, _element.createElement)(_components.SelectControl, {
        label: (0, _i18n.__)('Link to'),
        value: linkTo,
        onChange: this.setLinkTo,
        options: linkOptions
      }), shouldShowSizeOptions && (0, _element.createElement)(_components.SelectControl, {
        label: (0, _i18n.__)('Image size'),
        value: sizeSlug,
        options: imageSizeOptions,
        onChange: this.updateImagesSize
      }))), noticeUI, (0, _element.createElement)(_gallery.default, (0, _extends2.default)({}, this.props, {
        selectedImage: this.state.selectedImage,
        mediaPlaceholder: mediaPlaceholder,
        onMoveBackward: this.onMoveBackward,
        onMoveForward: this.onMoveForward,
        onRemoveImage: this.onRemoveImage,
        onSelectImage: this.onSelectImage,
        onDeselectImage: this.onDeselectImage,
        onSetImageAttributes: this.setImageAttributes,
        onFocusGalleryCaption: this.onFocusGalleryCaption,
        insertBlocksAfter: insertBlocksAfter
      })));
    }
  }]);
  return GalleryEdit;
}(_element.Component);

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref7) {
  var ids = _ref7.attributes.ids,
      isSelected = _ref7.isSelected;

  var _select = select('core'),
      getMedia = _select.getMedia;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  var _getSettings = getSettings(),
      imageSizes = _getSettings.imageSizes,
      mediaUpload = _getSettings.mediaUpload;

  var resizedImages = {};

  if (isSelected) {
    resizedImages = (0, _lodash.reduce)(ids, function (currentResizedImages, id) {
      if (!id) {
        return currentResizedImages;
      }

      var image = getMedia(id);
      var sizes = (0, _lodash.reduce)(imageSizes, function (currentSizes, size) {
        var defaultUrl = (0, _lodash.get)(image, ['sizes', size.slug, 'url']);
        var mediaDetailsUrl = (0, _lodash.get)(image, ['media_details', 'sizes', size.slug, 'source_url']);
        return _objectSpread(_objectSpread({}, currentSizes), {}, (0, _defineProperty2.default)({}, size.slug, defaultUrl || mediaDetailsUrl));
      }, {});
      return _objectSpread(_objectSpread({}, currentResizedImages), {}, (0, _defineProperty2.default)({}, parseInt(id, 10), sizes));
    }, {});
  }

  return {
    imageSizes: imageSizes,
    mediaUpload: mediaUpload,
    resizedImages: resizedImages
  };
}), _components.withNotices, (0, _viewport.withViewportMatch)({
  isNarrow: '< small'
})])(GalleryEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map