"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _blob = require("@wordpress/blob");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _blocks = require("@wordpress/blocks");

var _util = require("../embed/util");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ALLOWED_MEDIA_TYPES = ['audio'];

function AudioEdit(_ref) {
  var attributes = _ref.attributes,
      noticeOperations = _ref.noticeOperations,
      setAttributes = _ref.setAttributes,
      onReplace = _ref.onReplace,
      isSelected = _ref.isSelected,
      noticeUI = _ref.noticeUI,
      insertBlocksAfter = _ref.insertBlocksAfter;
  var id = attributes.id,
      autoplay = attributes.autoplay,
      caption = attributes.caption,
      loop = attributes.loop,
      preload = attributes.preload,
      src = attributes.src;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  var mediaUpload = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return getSettings().mediaUpload;
  }, []);
  (0, _element.useEffect)(function () {
    if (!id && (0, _blob.isBlobURL)(src)) {
      var file = (0, _blob.getBlobByURL)(src);

      if (file) {
        mediaUpload({
          filesList: [file],
          onFileChange: function onFileChange(_ref2) {
            var _ref3 = (0, _slicedToArray2.default)(_ref2, 1),
                _ref3$ = _ref3[0],
                mediaId = _ref3$.id,
                url = _ref3$.url;

            setAttributes({
              id: mediaId,
              src: url
            });
          },
          onError: function onError(e) {
            setAttributes({
              src: undefined,
              id: undefined
            });
            noticeOperations.createErrorNotice(e);
          },
          allowedTypes: ALLOWED_MEDIA_TYPES
        });
      }
    }
  }, []);

  function toggleAttribute(attribute) {
    return function (newValue) {
      setAttributes((0, _defineProperty2.default)({}, attribute, newValue));
    };
  }

  function onSelectURL(newSrc) {
    // Set the block's src from the edit component's state, and switch off
    // the editing UI.
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

  function getAutoplayHelp(checked) {
    return checked ? (0, _i18n.__)('Note: Autoplaying audio may cause usability issues for some visitors.') : null;
  } // const { setAttributes, isSelected, noticeUI } = this.props;


  function onSelectAudio(media) {
    if (!media || !media.url) {
      // in this case there was an error and we should continue in the editing state
      // previous attributes should be removed because they may be temporary blob urls
      setAttributes({
        src: undefined,
        id: undefined
      });
      return;
    } // sets the block's attribute and updates the edit component from the
    // selected media, then switches off the editing UI


    setAttributes({
      src: media.url,
      id: media.id
    });
  }

  if (!src) {
    return (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
      icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
        icon: _icons.audio
      }),
      onSelect: onSelectAudio,
      onSelectURL: onSelectURL,
      accept: "audio/*",
      allowedTypes: ALLOWED_MEDIA_TYPES,
      value: attributes,
      notices: noticeUI,
      onError: onUploadError
    }));
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
    mediaId: id,
    mediaURL: src,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "audio/*",
    onSelect: onSelectAudio,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Audio settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Autoplay'),
    onChange: toggleAttribute('autoplay'),
    checked: autoplay,
    help: getAutoplayHelp
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Loop'),
    onChange: toggleAttribute('loop'),
    checked: loop
  }), (0, _element.createElement)(_components.SelectControl, {
    label: (0, _i18n.__)('Preload'),
    value: preload || '' // `undefined` is required for the preload attribute to be unset.
    ,
    onChange: function onChange(value) {
      return setAttributes({
        preload: value || undefined
      });
    },
    options: [{
      value: '',
      label: (0, _i18n.__)('Browser default')
    }, {
      value: 'auto',
      label: (0, _i18n.__)('Auto')
    }, {
      value: 'metadata',
      label: (0, _i18n.__)('Metadata')
    }, {
      value: 'none',
      label: (0, _i18n.__)('None')
    }]
  }))), (0, _element.createElement)("figure", blockWrapperProps, (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)("audio", {
    controls: "controls",
    src: src
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

var _default = (0, _components.withNotices)(AudioEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map