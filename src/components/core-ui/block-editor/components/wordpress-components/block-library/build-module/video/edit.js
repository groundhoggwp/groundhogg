import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { getBlobByURL, isBlobURL } from '@wordpress/blob';
import { BaseControl, Button, Disabled, PanelBody, withNotices } from '@wordpress/components';
import { BlockControls, BlockIcon, InspectorControls, MediaPlaceholder, MediaUpload, MediaUploadCheck, MediaReplaceFlow, RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useRef, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { video as icon } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { createUpgradedEmbedBlock } from '../embed/util';
import VideoCommonSettings from './edit-common-settings';
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
  var instanceId = useInstanceId(VideoEdit);
  var videoPlayer = useRef();
  var posterImageButton = useRef();
  var id = attributes.id,
      caption = attributes.caption,
      controls = attributes.controls,
      poster = attributes.poster,
      src = attributes.src;
  var mediaUpload = useSelect(function (select) {
    return select('core/block-editor').getSettings().mediaUpload;
  });
  useEffect(function () {
    if (!id && isBlobURL(src)) {
      var file = getBlobByURL(src);

      if (file) {
        mediaUpload({
          filesList: [file],
          onFileChange: function onFileChange(_ref2) {
            var _ref3 = _slicedToArray(_ref2, 1),
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
  useEffect(function () {
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

  var blockWrapperProps = useBlockWrapperProps();

  if (!src) {
    return createElement("div", blockWrapperProps, createElement(MediaPlaceholder, {
      icon: createElement(BlockIcon, {
        icon: icon
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
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(MediaReplaceFlow, {
    mediaId: id,
    mediaURL: src,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "video/*",
    onSelect: onSelectVideo,
    onSelectURL: onSelectURL,
    onError: onUploadError
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Video settings')
  }, createElement(VideoCommonSettings, {
    setAttributes: setAttributes,
    attributes: attributes
  }), createElement(MediaUploadCheck, null, createElement(BaseControl, {
    className: "editor-video-poster-control"
  }, createElement(BaseControl.VisualLabel, null, __('Poster image')), createElement(MediaUpload, {
    title: __('Select poster image'),
    onSelect: onSelectPoster,
    allowedTypes: VIDEO_POSTER_ALLOWED_MEDIA_TYPES,
    render: function render(_ref4) {
      var open = _ref4.open;
      return createElement(Button, {
        isPrimary: true,
        onClick: open,
        ref: posterImageButton,
        "aria-describedby": videoPosterDescription
      }, !poster ? __('Select') : __('Replace'));
    }
  }), createElement("p", {
    id: videoPosterDescription,
    hidden: true
  }, poster ? sprintf(
  /* translators: %s: poster image URL. */
  __('The current poster image url is %s'), poster) : __('There is no poster image currently selected')), !!poster && createElement(Button, {
    onClick: onRemovePoster,
    isTertiary: true
  }, __('Remove')))))), createElement("figure", blockWrapperProps, createElement(Disabled, null, createElement("video", {
    controls: controls,
    poster: poster,
    src: src,
    ref: videoPlayer
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

export default withNotices(VideoEdit);
//# sourceMappingURL=edit.js.map