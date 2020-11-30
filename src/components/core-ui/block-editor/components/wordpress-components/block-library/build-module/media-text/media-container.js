import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { ResizableBox, withNotices } from '@wordpress/components';
import { BlockControls, BlockIcon, MediaPlaceholder, MediaReplaceFlow } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useViewportMatch } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import icon from './media-container-icon';
/**
 * Constants
 */

var ALLOWED_MEDIA_TYPES = ['image', 'video'];
export function imageFillStyles(url, focalPoint) {
  return url ? {
    backgroundImage: "url(".concat(url, ")"),
    backgroundPosition: focalPoint ? "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%") : "50% 50%"
  } : {};
}

function ResizableBoxContainer(_ref) {
  var isSelected = _ref.isSelected,
      isStackedOnMobile = _ref.isStackedOnMobile,
      props = _objectWithoutProperties(_ref, ["isSelected", "isStackedOnMobile"]);

  var isMobile = useViewportMatch('small', '<');
  return createElement(ResizableBox, _extends({
    showHandle: isSelected && (!isMobile || !isStackedOnMobile)
  }, props));
}

function ToolbarEditButton(_ref2) {
  var mediaId = _ref2.mediaId,
      mediaUrl = _ref2.mediaUrl,
      onSelectMedia = _ref2.onSelectMedia;
  return createElement(BlockControls, null, createElement(MediaReplaceFlow, {
    mediaId: mediaId,
    mediaURL: mediaUrl,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "image/*,video/*",
    onSelect: onSelectMedia
  }));
}

function PlaceholderContainer(_ref3) {
  var className = _ref3.className,
      noticeOperations = _ref3.noticeOperations,
      noticeUI = _ref3.noticeUI,
      onSelectMedia = _ref3.onSelectMedia;

  var onUploadError = function onUploadError(message) {
    noticeOperations.removeAllNotices();
    noticeOperations.createErrorNotice(message);
  };

  return createElement(MediaPlaceholder, {
    icon: createElement(BlockIcon, {
      icon: icon
    }),
    labels: {
      title: __('Media area')
    },
    className: className,
    onSelect: onSelectMedia,
    accept: "image/*,video/*",
    allowedTypes: ALLOWED_MEDIA_TYPES,
    notices: noticeUI,
    onError: onUploadError
  });
}

function MediaContainer(props) {
  var className = props.className,
      commitWidthChange = props.commitWidthChange,
      focalPoint = props.focalPoint,
      imageFill = props.imageFill,
      isSelected = props.isSelected,
      isStackedOnMobile = props.isStackedOnMobile,
      mediaAlt = props.mediaAlt,
      mediaId = props.mediaId,
      mediaPosition = props.mediaPosition,
      mediaType = props.mediaType,
      mediaUrl = props.mediaUrl,
      mediaWidth = props.mediaWidth,
      onSelectMedia = props.onSelectMedia,
      onWidthChange = props.onWidthChange;

  var _useDispatch = useDispatch('core/block-editor'),
      toggleSelection = _useDispatch.toggleSelection;

  if (mediaType && mediaUrl) {
    var onResizeStart = function onResizeStart() {
      toggleSelection(false);
    };

    var onResize = function onResize(event, direction, elt) {
      onWidthChange(parseInt(elt.style.width));
    };

    var onResizeStop = function onResizeStop(event, direction, elt) {
      toggleSelection(true);
      commitWidthChange(parseInt(elt.style.width));
    };

    var enablePositions = {
      right: mediaPosition === 'left',
      left: mediaPosition === 'right'
    };
    var backgroundStyles = mediaType === 'image' && imageFill ? imageFillStyles(mediaUrl, focalPoint) : {};
    var mediaTypeRenderers = {
      image: function image() {
        return createElement("img", {
          src: mediaUrl,
          alt: mediaAlt
        });
      },
      video: function video() {
        return createElement("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    return createElement(ResizableBoxContainer, {
      as: "figure",
      className: classnames(className, 'editor-media-container__resizer'),
      style: backgroundStyles,
      size: {
        width: mediaWidth + '%'
      },
      minWidth: "10%",
      maxWidth: "100%",
      enable: enablePositions,
      onResizeStart: onResizeStart,
      onResize: onResize,
      onResizeStop: onResizeStop,
      axis: "x",
      isSelected: isSelected,
      isStackedOnMobile: isStackedOnMobile
    }, createElement(ToolbarEditButton, {
      onSelectMedia: onSelectMedia,
      mediaUrl: mediaUrl,
      mediaId: mediaId
    }), (mediaTypeRenderers[mediaType] || noop)());
  }

  return createElement(PlaceholderContainer, props);
}

export default withNotices(MediaContainer);
//# sourceMappingURL=media-container.js.map