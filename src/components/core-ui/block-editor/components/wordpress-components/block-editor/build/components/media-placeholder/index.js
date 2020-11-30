"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.MediaPlaceholder = MediaPlaceholder;
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

var _icons = require("@wordpress/icons");

var _mediaUpload = _interopRequireDefault(require("../media-upload"));

var _check = _interopRequireDefault(require("../media-upload/check"));

var _urlPopover = _interopRequireDefault(require("../url-popover"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var InsertFromURLPopover = function InsertFromURLPopover(_ref) {
  var src = _ref.src,
      onChange = _ref.onChange,
      onSubmit = _ref.onSubmit,
      onClose = _ref.onClose;
  return (0, _element.createElement)(_urlPopover.default, {
    onClose: onClose
  }, (0, _element.createElement)("form", {
    className: "block-editor-media-placeholder__url-input-form",
    onSubmit: onSubmit
  }, (0, _element.createElement)("input", {
    className: "block-editor-media-placeholder__url-input-field",
    type: "url",
    "aria-label": (0, _i18n.__)('URL'),
    placeholder: (0, _i18n.__)('Paste or type URL'),
    onChange: onChange,
    value: src
  }), (0, _element.createElement)(_components.Button, {
    className: "block-editor-media-placeholder__url-input-submit-button",
    icon: _icons.keyboardReturn,
    label: (0, _i18n.__)('Apply'),
    type: "submit"
  })));
};

function MediaPlaceholder(_ref2) {
  var _ref2$value = _ref2.value,
      value = _ref2$value === void 0 ? {} : _ref2$value,
      allowedTypes = _ref2.allowedTypes,
      className = _ref2.className,
      icon = _ref2.icon,
      _ref2$labels = _ref2.labels,
      labels = _ref2$labels === void 0 ? {} : _ref2$labels,
      mediaPreview = _ref2.mediaPreview,
      notices = _ref2.notices,
      isAppender = _ref2.isAppender,
      accept = _ref2.accept,
      addToGallery = _ref2.addToGallery,
      _ref2$multiple = _ref2.multiple,
      multiple = _ref2$multiple === void 0 ? false : _ref2$multiple,
      dropZoneUIOnly = _ref2.dropZoneUIOnly,
      disableDropZone = _ref2.disableDropZone,
      disableMediaButtons = _ref2.disableMediaButtons,
      onError = _ref2.onError,
      onSelect = _ref2.onSelect,
      onCancel = _ref2.onCancel,
      onSelectURL = _ref2.onSelectURL,
      onDoubleClick = _ref2.onDoubleClick,
      _ref2$onFilesPreUploa = _ref2.onFilesPreUpload,
      onFilesPreUpload = _ref2$onFilesPreUploa === void 0 ? _lodash.noop : _ref2$onFilesPreUploa,
      _ref2$onHTMLDrop = _ref2.onHTMLDrop,
      onHTMLDrop = _ref2$onHTMLDrop === void 0 ? _lodash.noop : _ref2$onHTMLDrop,
      children = _ref2.children;
  var mediaUpload = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return getSettings().mediaUpload;
  }, []);

  var _useState = (0, _element.useState)(''),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      src = _useState2[0],
      setSrc = _useState2[1];

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isURLInputVisible = _useState4[0],
      setIsURLInputVisible = _useState4[1];

  (0, _element.useEffect)(function () {
    var _value$src;

    setSrc((_value$src = value === null || value === void 0 ? void 0 : value.src) !== null && _value$src !== void 0 ? _value$src : '');
  }, [value]);

  var onlyAllowsImages = function onlyAllowsImages() {
    if (!allowedTypes || allowedTypes.length === 0) {
      return false;
    }

    return allowedTypes.every(function (allowedType) {
      return allowedType === 'image' || allowedType.startsWith('image/');
    });
  };

  var onChangeSrc = function onChangeSrc(event) {
    setSrc(event.target.value);
  };

  var openURLInput = function openURLInput() {
    setIsURLInputVisible(true);
  };

  var closeURLInput = function closeURLInput() {
    setIsURLInputVisible(false);
  };

  var onSubmitSrc = function onSubmitSrc(event) {
    event.preventDefault();

    if (src && onSelectURL) {
      onSelectURL(src);
      closeURLInput();
    }
  };

  var onFilesUpload = function onFilesUpload(files) {
    onFilesPreUpload(files);
    var setMedia;

    if (multiple) {
      if (addToGallery) {
        // Since the setMedia function runs multiple times per upload group
        // and is passed newMedia containing every item in its group each time, we must
        // filter out whatever this upload group had previously returned to the
        // gallery before adding and returning the image array with replacement newMedia
        // values.
        // Define an array to store urls from newMedia between subsequent function calls.
        var lastMediaPassed = [];

        setMedia = function setMedia(newMedia) {
          // Remove any images this upload group is responsible for (lastMediaPassed).
          // Their replacements are contained in newMedia.
          var filteredMedia = (value !== null && value !== void 0 ? value : []).filter(function (item) {
            // If Item has id, only remove it if lastMediaPassed has an item with that id.
            if (item.id) {
              return !lastMediaPassed.some( // Be sure to convert to number for comparison.
              function (_ref3) {
                var id = _ref3.id;
                return Number(id) === Number(item.id);
              });
            } // Compare transient images via .includes since gallery may append extra info onto the url.


            return !lastMediaPassed.some(function (_ref4) {
              var urlSlug = _ref4.urlSlug;
              return item.url.includes(urlSlug);
            });
          }); // Return the filtered media array along with newMedia.

          onSelect(filteredMedia.concat(newMedia)); // Reset lastMediaPassed and set it with ids and urls from newMedia.

          lastMediaPassed = newMedia.map(function (media) {
            // Add everything up to '.fileType' to compare via .includes.
            var cutOffIndex = media.url.lastIndexOf('.');
            var urlSlug = media.url.slice(0, cutOffIndex);
            return {
              id: media.id,
              urlSlug: urlSlug
            };
          });
        };
      } else {
        setMedia = onSelect;
      }
    } else {
      setMedia = function setMedia(_ref5) {
        var _ref6 = (0, _slicedToArray2.default)(_ref5, 1),
            media = _ref6[0];

        return onSelect(media);
      };
    }

    mediaUpload({
      allowedTypes: allowedTypes,
      filesList: files,
      onFileChange: setMedia,
      onError: onError
    });
  };

  var onUpload = function onUpload(event) {
    onFilesUpload(event.target.files);
  };

  var renderPlaceholder = function renderPlaceholder(content, onClick) {
    var instructions = labels.instructions,
        title = labels.title;

    if (!mediaUpload && !onSelectURL) {
      instructions = (0, _i18n.__)('To edit this block, you need permission to upload media.');
    }

    if (instructions === undefined || title === undefined) {
      var typesAllowed = allowedTypes !== null && allowedTypes !== void 0 ? allowedTypes : [];

      var _typesAllowed = (0, _slicedToArray2.default)(typesAllowed, 1),
          firstAllowedType = _typesAllowed[0];

      var isOneType = 1 === typesAllowed.length;
      var isAudio = isOneType && 'audio' === firstAllowedType;
      var isImage = isOneType && 'image' === firstAllowedType;
      var isVideo = isOneType && 'video' === firstAllowedType;

      if (instructions === undefined && mediaUpload) {
        instructions = (0, _i18n.__)('Upload a media file or pick one from your media library.');

        if (isAudio) {
          instructions = (0, _i18n.__)('Upload an audio file, pick one from your media library, or add one with a URL.');
        } else if (isImage) {
          instructions = (0, _i18n.__)('Upload an image file, pick one from your media library, or add one with a URL.');
        } else if (isVideo) {
          instructions = (0, _i18n.__)('Upload a video file, pick one from your media library, or add one with a URL.');
        }
      }

      if (title === undefined) {
        title = (0, _i18n.__)('Media');

        if (isAudio) {
          title = (0, _i18n.__)('Audio');
        } else if (isImage) {
          title = (0, _i18n.__)('Image');
        } else if (isVideo) {
          title = (0, _i18n.__)('Video');
        }
      }
    }

    var placeholderClassName = (0, _classnames.default)('block-editor-media-placeholder', className, {
      'is-appender': isAppender
    });
    return (0, _element.createElement)(_components.Placeholder, {
      icon: icon,
      label: title,
      instructions: instructions,
      className: placeholderClassName,
      notices: notices,
      onClick: onClick,
      onDoubleClick: onDoubleClick,
      preview: mediaPreview
    }, content, children);
  };

  var renderDropZone = function renderDropZone() {
    if (disableDropZone) {
      return null;
    }

    return (0, _element.createElement)(_components.DropZone, {
      onFilesDrop: onFilesUpload,
      onHTMLDrop: onHTMLDrop
    });
  };

  var renderCancelLink = function renderCancelLink() {
    return onCancel && (0, _element.createElement)(_components.Button, {
      className: "block-editor-media-placeholder__cancel-button",
      title: (0, _i18n.__)('Cancel'),
      isLink: true,
      onClick: onCancel
    }, (0, _i18n.__)('Cancel'));
  };

  var renderUrlSelectionUI = function renderUrlSelectionUI() {
    return onSelectURL && (0, _element.createElement)("div", {
      className: "block-editor-media-placeholder__url-input-container"
    }, (0, _element.createElement)(_components.Button, {
      className: "block-editor-media-placeholder__button",
      onClick: openURLInput,
      isPressed: isURLInputVisible,
      isTertiary: true
    }, (0, _i18n.__)('Insert from URL')), isURLInputVisible && (0, _element.createElement)(InsertFromURLPopover, {
      src: src,
      onChange: onChangeSrc,
      onSubmit: onSubmitSrc,
      onClose: closeURLInput
    }));
  };

  var renderMediaUploadChecked = function renderMediaUploadChecked() {
    var mediaLibraryButton = (0, _element.createElement)(_mediaUpload.default, {
      addToGallery: addToGallery,
      gallery: multiple && onlyAllowsImages(),
      multiple: multiple,
      onSelect: onSelect,
      allowedTypes: allowedTypes,
      value: Array.isArray(value) ? value.map(function (_ref7) {
        var id = _ref7.id;
        return id;
      }) : value.id,
      render: function render(_ref8) {
        var open = _ref8.open;
        return (0, _element.createElement)(_components.Button, {
          isTertiary: true,
          onClick: function onClick(event) {
            event.stopPropagation();
            open();
          }
        }, (0, _i18n.__)('Media Library'));
      }
    });

    if (mediaUpload && isAppender) {
      return (0, _element.createElement)(_element.Fragment, null, renderDropZone(), (0, _element.createElement)(_components.FormFileUpload, {
        onChange: onUpload,
        accept: accept,
        multiple: multiple,
        render: function render(_ref9) {
          var openFileDialog = _ref9.openFileDialog;
          var content = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.Button, {
            isPrimary: true,
            className: (0, _classnames.default)('block-editor-media-placeholder__button', 'block-editor-media-placeholder__upload-button')
          }, (0, _i18n.__)('Upload')), mediaLibraryButton, renderUrlSelectionUI(), renderCancelLink());
          return renderPlaceholder(content, openFileDialog);
        }
      }));
    }

    if (mediaUpload) {
      var content = (0, _element.createElement)(_element.Fragment, null, renderDropZone(), (0, _element.createElement)(_components.FormFileUpload, {
        isPrimary: true,
        className: (0, _classnames.default)('block-editor-media-placeholder__button', 'block-editor-media-placeholder__upload-button'),
        onChange: onUpload,
        accept: accept,
        multiple: multiple
      }, (0, _i18n.__)('Upload')), mediaLibraryButton, renderUrlSelectionUI(), renderCancelLink());
      return renderPlaceholder(content);
    }

    return renderPlaceholder(mediaLibraryButton);
  };

  if (dropZoneUIOnly || disableMediaButtons) {
    if (dropZoneUIOnly) {
      (0, _deprecated.default)('wp.blockEditor.MediaPlaceholder dropZoneUIOnly prop', {
        alternative: 'disableMediaButtons'
      });
    }

    return (0, _element.createElement)(_check.default, null, renderDropZone());
  }

  return (0, _element.createElement)(_check.default, {
    fallback: renderPlaceholder(renderUrlSelectionUI())
  }, renderMediaUploadChecked());
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-placeholder/README.md
 */


var _default = (0, _components.withFilters)('editor.MediaPlaceholder')(MediaPlaceholder);

exports.default = _default;
//# sourceMappingURL=index.js.map