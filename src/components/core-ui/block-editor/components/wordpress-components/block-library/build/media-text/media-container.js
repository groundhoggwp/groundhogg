"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.imageFillStyles = imageFillStyles;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _mediaContainerIcon = _interopRequireDefault(require("./media-container-icon"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Constants
 */
var ALLOWED_MEDIA_TYPES = ['image', 'video'];

function imageFillStyles(url, focalPoint) {
  return url ? {
    backgroundImage: "url(".concat(url, ")"),
    backgroundPosition: focalPoint ? "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%") : "50% 50%"
  } : {};
}

function ResizableBoxContainer(_ref) {
  var isSelected = _ref.isSelected,
      isStackedOnMobile = _ref.isStackedOnMobile,
      props = (0, _objectWithoutProperties2.default)(_ref, ["isSelected", "isStackedOnMobile"]);
  var isMobile = (0, _compose.useViewportMatch)('small', '<');
  return (0, _element.createElement)(_components.ResizableBox, (0, _extends2.default)({
    showHandle: isSelected && (!isMobile || !isStackedOnMobile)
  }, props));
}

function ToolbarEditButton(_ref2) {
  var mediaId = _ref2.mediaId,
      mediaUrl = _ref2.mediaUrl,
      onSelectMedia = _ref2.onSelectMedia;
  return (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
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

  return (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
    icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
      icon: _mediaContainerIcon.default
    }),
    labels: {
      title: (0, _i18n.__)('Media area')
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

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
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
        return (0, _element.createElement)("img", {
          src: mediaUrl,
          alt: mediaAlt
        });
      },
      video: function video() {
        return (0, _element.createElement)("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    return (0, _element.createElement)(ResizableBoxContainer, {
      as: "figure",
      className: (0, _classnames.default)(className, 'editor-media-container__resizer'),
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
    }, (0, _element.createElement)(ToolbarEditButton, {
      onSelectMedia: onSelectMedia,
      mediaUrl: mediaUrl,
      mediaId: mediaId
    }), (mediaTypeRenderers[mediaType] || _lodash.noop)());
  }

  return (0, _element.createElement)(PlaceholderContainer, props);
}

var _default = (0, _components.withNotices)(MediaContainer);

exports.default = _default;
//# sourceMappingURL=media-container.js.map