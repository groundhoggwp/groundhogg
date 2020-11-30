import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { uniqueId, noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { useState, createRef, renderToString } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { FormFileUpload, NavigableMenu, MenuItem, ToolbarGroup, ToolbarButton, Dropdown, withFilters } from '@wordpress/components';
import { withDispatch, useSelect } from '@wordpress/data';
import { DOWN } from '@wordpress/keycodes';
import { compose } from '@wordpress/compose';
import { upload, media as mediaIcon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import MediaUpload from '../media-upload';
import MediaUploadCheck from '../media-upload/check';
import LinkControl from '../link-control';

var MediaReplaceFlow = function MediaReplaceFlow(_ref) {
  var mediaURL = _ref.mediaURL,
      mediaId = _ref.mediaId,
      allowedTypes = _ref.allowedTypes,
      accept = _ref.accept,
      onSelect = _ref.onSelect,
      onSelectURL = _ref.onSelectURL,
      _ref$onFilesUpload = _ref.onFilesUpload,
      onFilesUpload = _ref$onFilesUpload === void 0 ? noop : _ref$onFilesUpload,
      _ref$name = _ref.name,
      name = _ref$name === void 0 ? __('Replace') : _ref$name,
      createNotice = _ref.createNotice,
      removeNotice = _ref.removeNotice;

  var _useState = useState(mediaURL),
      _useState2 = _slicedToArray(_useState, 2),
      mediaURLValue = _useState2[0],
      setMediaURLValue = _useState2[1];

  var mediaUpload = useSelect(function (select) {
    return select('core/block-editor').getSettings().mediaUpload;
  }, []);
  var editMediaButtonRef = createRef();
  var errorNoticeID = uniqueId('block-editor/media-replace-flow/error-notice/');

  var onError = function onError(message) {
    var errorElement = document.createElement('div');
    errorElement.innerHTML = renderToString(message); // The default error contains some HTML that,
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
    speak(__('The media file has been replaced'));
    removeNotice(errorNoticeID);
  };

  var selectURL = function selectURL(newURL) {
    onSelectURL(newURL);
  };

  var uploadFiles = function uploadFiles(event) {
    var files = event.target.files;
    onFilesUpload(files);

    var setMedia = function setMedia(_ref2) {
      var _ref3 = _slicedToArray(_ref2, 1),
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
    if (event.keyCode === DOWN) {
      event.preventDefault();
      event.stopPropagation();
      event.target.click();
    }
  };

  var POPOVER_PROPS = {
    isAlternate: true
  };
  return createElement(Dropdown, {
    popoverProps: POPOVER_PROPS,
    contentClassName: "block-editor-media-replace-flow__options",
    renderToggle: function renderToggle(_ref4) {
      var isOpen = _ref4.isOpen,
          onToggle = _ref4.onToggle;
      return createElement(ToolbarGroup, {
        className: "media-replace-flow"
      }, createElement(ToolbarButton, {
        ref: editMediaButtonRef,
        "aria-expanded": isOpen,
        "aria-haspopup": "true",
        onClick: onToggle,
        onKeyDown: openOnArrowDown
      }, name));
    },
    renderContent: function renderContent(_ref5) {
      var onClose = _ref5.onClose;
      return createElement(Fragment, null, createElement(NavigableMenu, {
        className: "block-editor-media-replace-flow__media-upload-menu"
      }, createElement(MediaUpload, {
        value: mediaId,
        onSelect: function onSelect(media) {
          return selectMedia(media);
        },
        allowedTypes: allowedTypes,
        render: function render(_ref6) {
          var open = _ref6.open;
          return createElement(MenuItem, {
            icon: mediaIcon,
            onClick: open
          }, __('Open Media Library'));
        }
      }), createElement(MediaUploadCheck, null, createElement(FormFileUpload, {
        onChange: function onChange(event) {
          uploadFiles(event, onClose);
        },
        accept: accept,
        render: function render(_ref7) {
          var openFileDialog = _ref7.openFileDialog;
          return createElement(MenuItem, {
            icon: upload,
            onClick: function onClick() {
              openFileDialog();
            }
          }, __('Upload'));
        }
      }))), onSelectURL && // eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
      createElement("form", {
        className: "block-editor-media-flow__url-input",
        onKeyDown: function onKeyDown(event) {
          event.stopPropagation();
        },
        onKeyPress: function onKeyPress(event) {
          event.stopPropagation();
        }
      }, createElement("span", {
        className: "block-editor-media-replace-flow__image-url-label"
      }, __('Current media URL:')), createElement(LinkControl, {
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

export default compose([withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice,
      removeNotice = _dispatch.removeNotice;

  return {
    createNotice: createNotice,
    removeNotice: removeNotice
  };
}), withFilters('editor.MediaReplaceFlow')])(MediaReplaceFlow);
//# sourceMappingURL=index.js.map