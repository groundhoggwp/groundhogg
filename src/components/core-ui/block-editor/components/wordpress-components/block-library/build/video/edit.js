"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _blob = require("@wordpress/blob");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _blocks = require("@wordpress/blocks");

var _util = require("../embed/util");

var _editCommonSettings = _interopRequireDefault(require("./edit-common-settings"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ALLOWED_MEDIA_TYPES = ['video'];
var VIDEO_POSTER_ALLOWED_MEDIA_TYPES = ['image'];

function VideoEdit(_ref) {
  var isSelected = _ref.isSelected,
      noticeUI = _ref.noticeUI,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      insertBlocksAfter = _ref.insertBlocksAfter,
      onReplace = _ref.onReplace,
      noticeOperations = _ref.noticeOperations;
  var instanceId = (0, _compose.useInstanceId)(VideoEdit);
  var videoPlayer = (0, _element.useRef)();
  var posterImageButton = (0, _element.useRef)();
  var id = attributes.id,
      caption = attributes.caption,
      controls = attributes.controls,
      poster = attributes.poster,
      src = attributes.src;
  var mediaUpload = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings().mediaUpload;
  });
  (0, _element.useEffect)(function () {
    if (!id && (0, _blob.isBlobURL)(src)) {
      var file = (0, _blob.getBlobByURL)(src);

      if (file) {
        mediaUpload({
          filesList: [file],
          onFileChange: function onFileChange(_ref2) {
            var _ref3 = (0, _slicedToArray2.default)(_ref2, 1),
                url = _ref3[0].url;

            setAttributes({
              src: url
            });
          },
          onError: function onError(message) {
            noticeOperations.createErrorNotice(message);
          },
          allowedTypes: ALLOWED_MEDIA_TYPES
        });
      }
    }
  }, []);
  (0, _element.useEffect)(function () {
    // Placeholder may be rendered.
    if (videoPlayer.current) {
      videoPlayer.current.load();
    }
  }, [poster]);

  function onSelectVideo(media) {
    if (!media || !media.url) {
      // in this case there was an error
      // previous attributes should be removed
      // because they may be temporary blob urls
      setAttributes({
        src: undefined,
        id: undefined
      });
      return;
    } // sets the block's attribute and updates the edit component from the
    // selected media


    setAttributes({
      src: media.url,
      id: media.id
    });
  }

  function onSelectURL(newSrc) {
    if (newSrc !== src) {
      // Check if there's an embed block that handles this URL.
      var embedBlock = (0, _util.createUpgradedEmbedBlock)({
        attributes: {
          url: newSrc
        }
      });

      if (undefined !== embedBlock) {
        onReplace(embedBlock);
        return;
      }

      setAttributes({
        src: newSrc,
        id: undefined
      });
    }
  }

  function onUploadError(message) {
    noticeOperations.removeAllNotices();
    noticeOperations.createErrorNotice(message);
  }

  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();

  if (!src) {
    return (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
      icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
        icon: _icons.video
      }),
      onSelect: onSelectVideo,
      onSelectURL: onSelectURL,
      accept: "video/*",
      allowedTypes: ALLOWED_MEDIA_TYPES,
      value: attributes,
      notices: noticeUI,
      onError: onUploadError
    }));
  }

  function onSelectPoster(image) {
    setAttributes({
      poster: image.url
    });
  }

  function onRemovePoster() {
    setAttributes({
      poster: ''
    }); // Move focus back to the Media Upload button.

    this.posterImageButton.current.focus();
  }

  var videoPosterDescription = "video-block__poster-image-description-".concat(instanceId);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
    mediaId: id,
    mediaURL: src,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "video/*",
    onSelect: onSelectVideo,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Video settings')
  }, (0, _element.createElement)(_editCommonSettings.default, {
    setAttributes: setAttributes,
    attributes: attributes
  }), (0, _element.createElement)(_blockEditor.MediaUploadCheck, null, (0, _element.createElement)(_components.BaseControl, {
    className: "editor-video-poster-control"
  }, (0, _element.createElement)(_components.BaseControl.VisualLabel, null, (0, _i18n.__)('Poster image')), (0, _element.createElement)(_blockEditor.MediaUpload, {
    title: (0, _i18n.__)('Select poster image'),
    onSelect: onSelectPoster,
    allowedTypes: VIDEO_POSTER_ALLOWED_MEDIA_TYPES,
    render: function render(_ref4) {
      var open = _ref4.open;
      return (0, _element.createElement)(_components.Button, {
        isPrimary: true,
        onClick: open,
        ref: posterImageButton,
        "aria-describedby": videoPosterDescription
      }, !poster ? (0, _i18n.__)('Select') : (0, _i18n.__)('Replace'));
    }
  }), (0, _element.createElement)("p", {
    id: videoPosterDescription,
    hidden: true
  }, poster ? (0, _i18n.sprintf)(
  /* translators: %s: poster image URL. */
  (0, _i18n.__)('The current poster image url is %s'), poster) : (0, _i18n.__)('There is no poster image currently selected')), !!poster && (0, _element.createElement)(_components.Button, {
    onClick: onRemovePoster,
    isTertiary: true
  }, (0, _i18n.__)('Remove')))))), (0, _element.createElement)("figure", blockWrapperProps, (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)("video", {
    controls: controls,
    poster: poster,
    src: src,
    ref: videoPlayer
  })), (!_blockEditor.RichText.isEmpty(caption) || isSelected) && (0, _element.createElement)(_blockEditor.RichText, {
    tagName: "figcaption",
    placeholder: (0, _i18n.__)('Write caption…'),
    value: caption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    inlineToolbar: true,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
    }
  })));
}

var _default = (0, _components.withNotices)(VideoEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map