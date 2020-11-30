import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, Text, TouchableWithoutFeedback } from 'react-native';
import { uniqWith } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { MediaUpload, MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO } from '@wordpress/block-editor';
import { withPreferredColorScheme } from '@wordpress/compose';
import { useRef } from '@wordpress/element';
import { Icon, plusCircleFilled } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import styles from './styles.scss'; // remove duplicates after gallery append

var dedupMedia = function dedupMedia(media) {
  return uniqWith(media, function (media1, media2) {
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

  var mediaRef = useRef(value);
  mediaRef.current = value; // append and deduplicate media array for gallery use case

  var setMedia = multiple && addToGallery ? function (selected) {
    return onSelect(dedupMedia([].concat(_toConsumableArray(mediaRef.current), _toConsumableArray(selected))));
  } : onSelect;
  var isOneType = allowedTypes.length === 1;
  var isImage = isOneType && allowedTypes.includes(MEDIA_TYPE_IMAGE);
  var isVideo = isOneType && allowedTypes.includes(MEDIA_TYPE_VIDEO);
  var placeholderTitle = labels.title;

  if (placeholderTitle === undefined) {
    placeholderTitle = __('Media');

    if (isImage) {
      placeholderTitle = __('Image');
    } else if (isVideo) {
      placeholderTitle = __('Video');
    }
  }

  var instructions = labels.instructions;

  if (instructions === undefined) {
    if (isImage) {
      instructions = __('ADD IMAGE');
    } else if (isVideo) {
      instructions = __('ADD VIDEO');
    } else {
      instructions = __('ADD IMAGE OR VIDEO');
    }
  }

  var accessibilityHint = __('Double tap to select');

  if (isImage) {
    accessibilityHint = __('Double tap to select an image');
  } else if (isVideo) {
    accessibilityHint = __('Double tap to select a video');
  }

  var emptyStateTitleStyle = getStylesFromColorScheme(styles.emptyStateTitle, styles.emptyStateTitleDark);
  var addMediaButtonStyle = getStylesFromColorScheme(styles.addMediaButton, styles.addMediaButtonDark);

  var renderContent = function renderContent() {
    if (isAppender === undefined || !isAppender) {
      return createElement(Fragment, null, createElement(View, {
        style: styles.modalIcon
      }, icon), createElement(Text, {
        style: emptyStateTitleStyle
      }, placeholderTitle), children, createElement(Text, {
        style: styles.emptyStateDescription
      }, instructions));
    } else if (isAppender && !disableMediaButtons) {
      return createElement(Icon, {
        icon: plusCircleFilled,
        style: addMediaButtonStyle,
        color: addMediaButtonStyle.color,
        size: addMediaButtonStyle.size
      });
    }
  };

  if (isAppender && disableMediaButtons) {
    return null;
  }

  var appenderStyle = getStylesFromColorScheme(styles.appender, styles.appenderDark);
  var emptyStateContainerStyle = getStylesFromColorScheme(styles.emptyStateContainer, styles.emptyStateContainerDark);
  return createElement(View, {
    style: {
      flex: 1
    }
  }, createElement(MediaUpload, {
    allowedTypes: allowedTypes,
    onSelect: setMedia,
    __experimentalOnlyMediaLibrary: __experimentalOnlyMediaLibrary,
    multiple: multiple,
    isReplacingMedia: false,
    render: function render(_ref) {
      var open = _ref.open,
          getMediaOptions = _ref.getMediaOptions;
      return createElement(TouchableWithoutFeedback, {
        accessibilityLabel: sprintf(
        /* translators: accessibility text for the media block empty state. %s: media type */
        __('%s block. Empty'), placeholderTitle),
        accessibilityRole: 'button',
        accessibilityHint: accessibilityHint,
        onPress: function onPress(event) {
          props.onFocus(event);
          open();
        }
      }, createElement(View, {
        style: [[emptyStateContainerStyle, height && {
          height: height
        }, backgroundColor && {
          backgroundColor: backgroundColor
        }], isAppender && appenderStyle]
      }, getMediaOptions(), !hideContent && renderContent()));
    }
  }));
}

export default withPreferredColorScheme(MediaPlaceholder);
//# sourceMappingURL=index.native.js.map