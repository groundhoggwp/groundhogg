import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { getBlobByURL, isBlobURL } from '@wordpress/blob';
import { Disabled, PanelBody, SelectControl, ToggleControl, withNotices } from '@wordpress/components';
import { BlockControls, BlockIcon, InspectorControls, MediaPlaceholder, MediaReplaceFlow, RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { audio as icon } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { createUpgradedEmbedBlock } from '../embed/util';
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
  var blockWrapperProps = useBlockWrapperProps();
  var mediaUpload = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return getSettings().mediaUpload;
  }, []);
  useEffect(function () {
    if (!id && isBlobURL(src)) {
      var file = getBlobByURL(src);

      if (file) {
        mediaUpload({
          filesList: [file],
          onFileChange: function onFileChange(_ref2) {
            var _ref3 = _slicedToArray(_ref2, 1),
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
      setAttributes(_defineProperty({}, attribute, newValue));
    };
  }

  function onSelectURL(newSrc) {
    // Set the block's src from the edit component's state, and switch off
    // the editing UI.
    if (newSrc !== src) {
      // Check if there's an embed block that handles this URL.
      var embedBlock = createUpgradedEmbedBlock({
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
    return checked ? __('Note: Autoplaying audio may cause usability issues for some visitors.') : null;
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
    return createElement("div", blockWrapperProps, createElement(MediaPlaceholder, {
      icon: createElement(BlockIcon, {
        icon: icon
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

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(MediaReplaceFlow, {
    mediaId: id,
    mediaURL: src,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "audio/*",
    onSelect: onSelectAudio,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Audio settings')
  }, createElement(ToggleControl, {
    label: __('Autoplay'),
    onChange: toggleAttribute('autoplay'),
    checked: autoplay,
    help: getAutoplayHelp
  }), createElement(ToggleControl, {
    label: __('Loop'),
    onChange: toggleAttribute('loop'),
    checked: loop
  }), createElement(SelectControl, {
    label: __('Preload'),
    value: preload || '' // `undefined` is required for the preload attribute to be unset.
    ,
    onChange: function onChange(value) {
      return setAttributes({
        preload: value || undefined
      });
    },
    options: [{
      value: '',
      label: __('Browser default')
    }, {
      value: 'auto',
      label: __('Auto')
    }, {
      value: 'metadata',
      label: __('Metadata')
    }, {
      value: 'none',
      label: __('None')
    }]
  }))), createElement("figure", blockWrapperProps, createElement(Disabled, null, createElement("audio", {
    controls: "controls",
    src: src
  })), (!RichText.isEmpty(caption) || isSelected) && createElement(RichText, {
    tagName: "figcaption",
    placeholder: __('Write caption…'),
    value: caption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    inlineToolbar: true,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter(createBlock('core/paragraph'));
    }
  })));
}

export default withNotices(AudioEdit);
//# sourceMappingURL=edit.js.map