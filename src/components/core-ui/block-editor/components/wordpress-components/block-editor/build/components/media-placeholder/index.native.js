"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
// remove duplicates after gallery append
var dedupMedia = function dedupMedia(media) {
  return (0, _lodash.uniqWith)(media, function (media1, media2) {
    return media1.id === media2.id || media1.url === media2.url;
  });
};

function MediaPlaceholder(props) {
  var addToGallery = props.addToGallery,
      _props$allowedTypes = props.allowedTypes,
      allowedTypes = _props$allowedTypes === void 0 ? [] : _props$allowedTypes,
      _props$labels = props.labels,
      labels = _props$labels === void 0 ? {} : _props$labels,
      icon = props.icon,
      onSelect = props.onSelect,
      __experimentalOnlyMediaLibrary = props.__experimentalOnlyMediaLibrary,
      isAppender = props.isAppender,
      disableMediaButtons = props.disableMediaButtons,
      getStylesFromColorScheme = props.getStylesFromColorScheme,
      multiple = props.multiple,
      _props$value = props.value,
      value = _props$value === void 0 ? [] : _props$value,
      children = props.children,
      height = props.height,
      backgroundColor = props.backgroundColor,
      hideContent = props.hideContent; // use ref to keep media array current for callbacks during rerenders

  var mediaRef = (0, _element.useRef)(value);
  mediaRef.current = value; // append and deduplicate media array for gallery use case

  var setMedia = multiple && addToGallery ? function (selected) {
    return onSelect(dedupMedia([].concat((0, _toConsumableArray2.default)(mediaRef.current), (0, _toConsumableArray2.default)(selected))));
  } : onSelect;
  var isOneType = allowedTypes.length === 1;
  var isImage = isOneType && allowedTypes.includes(_blockEditor.MEDIA_TYPE_IMAGE);
  var isVideo = isOneType && allowedTypes.includes(_blockEditor.MEDIA_TYPE_VIDEO);
  var placeholderTitle = labels.title;

  if (placeholderTitle === undefined) {
    placeholderTitle = (0, _i18n.__)('Media');

    if (isImage) {
      placeholderTitle = (0, _i18n.__)('Image');
    } else if (isVideo) {
      placeholderTitle = (0, _i18n.__)('Video');
    }
  }

  var instructions = labels.instructions;

  if (instructions === undefined) {
    if (isImage) {
      instructions = (0, _i18n.__)('ADD IMAGE');
    } else if (isVideo) {
      instructions = (0, _i18n.__)('ADD VIDEO');
    } else {
      instructions = (0, _i18n.__)('ADD IMAGE OR VIDEO');
    }
  }

  var accessibilityHint = (0, _i18n.__)('Double tap to select');

  if (isImage) {
    accessibilityHint = (0, _i18n.__)('Double tap to select an image');
  } else if (isVideo) {
    accessibilityHint = (0, _i18n.__)('Double tap to select a video');
  }

  var emptyStateTitleStyle = getStylesFromColorScheme(_styles.default.emptyStateTitle, _styles.default.emptyStateTitleDark);
  var addMediaButtonStyle = getStylesFromColorScheme(_styles.default.addMediaButton, _styles.default.addMediaButtonDark);

  var renderContent = function renderContent() {
    if (isAppender === undefined || !isAppender) {
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_reactNative.View, {
        style: _styles.default.modalIcon
      }, icon), (0, _element.createElement)(_reactNative.Text, {
        style: emptyStateTitleStyle
      }, placeholderTitle), children, (0, _element.createElement)(_reactNative.Text, {
        style: _styles.default.emptyStateDescription
      }, instructions));
    } else if (isAppender && !disableMediaButtons) {
      return (0, _element.createElement)(_icons.Icon, {
        icon: _icons.plusCircleFilled,
        style: addMediaButtonStyle,
        color: addMediaButtonStyle.color,
        size: addMediaButtonStyle.size
      });
    }
  };

  if (isAppender && disableMediaButtons) {
    return null;
  }

  var appenderStyle = getStylesFromColorScheme(_styles.default.appender, _styles.default.appenderDark);
  var emptyStateContainerStyle = getStylesFromColorScheme(_styles.default.emptyStateContainer, _styles.default.emptyStateContainerDark);
  return (0, _element.createElement)(_reactNative.View, {
    style: {
      flex: 1
    }
  }, (0, _element.createElement)(_blockEditor.MediaUpload, {
    allowedTypes: allowedTypes,
    onSelect: setMedia,
    __experimentalOnlyMediaLibrary: __experimentalOnlyMediaLibrary,
    multiple: multiple,
    isReplacingMedia: false,
    render: function render(_ref) {
      var open = _ref.open,
          getMediaOptions = _ref.getMediaOptions;
      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        accessibilityLabel: (0, _i18n.sprintf)(
        /* translators: accessibility text for the media block empty state. %s: media type */
        (0, _i18n.__)('%s block. Empty'), placeholderTitle),
        accessibilityRole: 'button',
        accessibilityHint: accessibilityHint,
        onPress: function onPress(event) {
          props.onFocus(event);
          open();
        }
      }, (0, _element.createElement)(_reactNative.View, {
        style: [[emptyStateContainerStyle, height && {
          height: height
        }, backgroundColor && {
          backgroundColor: backgroundColor
        }], isAppender && appenderStyle]
      }, getMediaOptions(), !hideContent && renderContent()));
    }
  }));
}

var _default = (0, _compose.withPreferredColorScheme)(MediaPlaceholder);

exports.default = _default;
//# sourceMappingURL=index.native.js.map