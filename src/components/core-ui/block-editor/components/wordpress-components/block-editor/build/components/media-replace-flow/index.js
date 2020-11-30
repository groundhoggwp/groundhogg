"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _a11y = require("@wordpress/a11y");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _keycodes = require("@wordpress/keycodes");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _mediaUpload = _interopRequireDefault(require("../media-upload"));

var _check = _interopRequireDefault(require("../media-upload/check"));

var _linkControl = _interopRequireDefault(require("../link-control"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var MediaReplaceFlow = function MediaReplaceFlow(_ref) {
  var mediaURL = _ref.mediaURL,
      mediaId = _ref.mediaId,
      allowedTypes = _ref.allowedTypes,
      accept = _ref.accept,
      onSelect = _ref.onSelect,
      onSelectURL = _ref.onSelectURL,
      _ref$onFilesUpload = _ref.onFilesUpload,
      onFilesUpload = _ref$onFilesUpload === void 0 ? _lodash.noop : _ref$onFilesUpload,
      _ref$name = _ref.name,
      name = _ref$name === void 0 ? (0, _i18n.__)('Replace') : _ref$name,
      createNotice = _ref.createNotice,
      removeNotice = _ref.removeNotice;

  var _useState = (0, _element.useState)(mediaURL),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      mediaURLValue = _useState2[0],
      setMediaURLValue = _useState2[1];

  var mediaUpload = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings().mediaUpload;
  }, []);
  var editMediaButtonRef = (0, _element.createRef)();
  var errorNoticeID = (0, _lodash.uniqueId)('block-editor/media-replace-flow/error-notice/');

  var onError = function onError(message) {
    var errorElement = document.createElement('div');
    errorElement.innerHTML = (0, _element.renderToString)(message); // The default error contains some HTML that,
    // for example, makes the filename bold.
    // The notice, by default, accepts strings only and so
    // we need to remove the html from the error.

    var renderMsg = errorElement.textContent || errorElement.innerText || ''; // We need to set a timeout for showing the notice
    // so that VoiceOver and possibly other screen readers
    // can announce the error afer the toolbar button
    // regains focus once the upload dialog closes.
    // Otherwise VO simply skips over the notice and announces
    // the focused element and the open menu.

    setTimeout(function () {
      createNotice('error', renderMsg, {
        speak: true,
        id: errorNoticeID,
        isDismissible: true
      });
    }, 1000);
  };

  var selectMedia = function selectMedia(media) {
    onSelect(media);
    setMediaURLValue(media.url);
    (0, _a11y.speak)((0, _i18n.__)('The media file has been replaced'));
    removeNotice(errorNoticeID);
  };

  var selectURL = function selectURL(newURL) {
    onSelectURL(newURL);
  };

  var uploadFiles = function uploadFiles(event) {
    var files = event.target.files;
    onFilesUpload(files);

    var setMedia = function setMedia(_ref2) {
      var _ref3 = (0, _slicedToArray2.default)(_ref2, 1),
          media = _ref3[0];

      selectMedia(media);
    };

    mediaUpload({
      allowedTypes: allowedTypes,
      filesList: files,
      onFileChange: setMedia,
      onError: onError
    });
  };

  var openOnArrowDown = function openOnArrowDown(event) {
    if (event.keyCode === _keycodes.DOWN) {
      event.preventDefault();
      event.stopPropagation();
      event.target.click();
    }
  };

  var POPOVER_PROPS = {
    isAlternate: true
  };
  return (0, _element.createElement)(_components.Dropdown, {
    popoverProps: POPOVER_PROPS,
    contentClassName: "block-editor-media-replace-flow__options",
    renderToggle: function renderToggle(_ref4) {
      var isOpen = _ref4.isOpen,
          onToggle = _ref4.onToggle;
      return (0, _element.createElement)(_components.ToolbarGroup, {
        className: "media-replace-flow"
      }, (0, _element.createElement)(_components.ToolbarButton, {
        ref: editMediaButtonRef,
        "aria-expanded": isOpen,
        "aria-haspopup": "true",
        onClick: onToggle,
        onKeyDown: openOnArrowDown
      }, name));
    },
    renderContent: function renderContent(_ref5) {
      var onClose = _ref5.onClose;
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.NavigableMenu, {
        className: "block-editor-media-replace-flow__media-upload-menu"
      }, (0, _element.createElement)(_mediaUpload.default, {
        value: mediaId,
        onSelect: function onSelect(media) {
          return selectMedia(media);
        },
        allowedTypes: allowedTypes,
        render: function render(_ref6) {
          var open = _ref6.open;
          return (0, _element.createElement)(_components.MenuItem, {
            icon: _icons.media,
            onClick: open
          }, (0, _i18n.__)('Open Media Library'));
        }
      }), (0, _element.createElement)(_check.default, null, (0, _element.createElement)(_components.FormFileUpload, {
        onChange: function onChange(event) {
          uploadFiles(event, onClose);
        },
        accept: accept,
        render: function render(_ref7) {
          var openFileDialog = _ref7.openFileDialog;
          return (0, _element.createElement)(_components.MenuItem, {
            icon: _icons.upload,
            onClick: function onClick() {
              openFileDialog();
            }
          }, (0, _i18n.__)('Upload'));
        }
      }))), onSelectURL && // eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
      (0, _element.createElement)("form", {
        className: "block-editor-media-flow__url-input",
        onKeyDown: function onKeyDown(event) {
          event.stopPropagation();
        },
        onKeyPress: function onKeyPress(event) {
          event.stopPropagation();
        }
      }, (0, _element.createElement)("span", {
        className: "block-editor-media-replace-flow__image-url-label"
      }, (0, _i18n.__)('Current media URL:')), (0, _element.createElement)(_linkControl.default, {
        value: {
          url: mediaURLValue
        },
        settings: [],
        showSuggestions: false,
        onChange: function onChange(_ref8) {
          var url = _ref8.url;
          setMediaURLValue(url);
          selectURL(url);
          editMediaButtonRef.current.focus();
        }
      })));
    }
  });
};

var _default = (0, _compose.compose)([(0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice,
      removeNotice = _dispatch.removeNotice;

  return {
    createNotice: createNotice,
    removeNotice: removeNotice
  };
}), (0, _components.withFilters)('editor.MediaReplaceFlow')])(MediaReplaceFlow);

exports.default = _default;
//# sourceMappingURL=index.js.map