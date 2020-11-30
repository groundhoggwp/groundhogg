import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, TouchableWithoutFeedback } from 'react-native';
import Video from 'react-native-video';
/**
 * WordPress dependencies
 */

import { requestImageFailedRetryDialog, requestImageUploadCancelDialog, requestImageFullscreenPreview, mediaUploadSync } from '@wordpress/react-native-bridge';
import { __ } from '@wordpress/i18n';
import { Icon, Image, ImageEditingButton, IMAGE_DEFAULT_FOCAL_POINT, PanelBody, RangeControl, BottomSheet, ToolbarButton, ToolbarGroup, Gradient, ColorPalette, ColorPicker, BottomSheetConsumer } from '@wordpress/components';
import { BlockControls, InnerBlocks, InspectorControls, MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO, MediaPlaceholder, MediaUpload, MediaUploadProgress, withColors, __experimentalUseGradient, __experimentalUseEditorFeature as useEditorFeature } from '@wordpress/block-editor';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { useEffect, useState, useRef } from '@wordpress/element';
import { cover as icon, replace, image, warning } from '@wordpress/icons';
import { getProtocol } from '@wordpress/url';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import { attributesFromMedia, COVER_MIN_HEIGHT, IMAGE_BACKGROUND_TYPE, VIDEO_BACKGROUND_TYPE } from './shared';
import OverlayColorSettings from './overlay-color-settings';
/**
 * Constants
 */

var ALLOWED_MEDIA_TYPES = [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO];
var INNER_BLOCKS_TEMPLATE = [['core/paragraph', {
  align: 'center',
  placeholder: __('Write title…')
}]];
var COVER_MAX_HEIGHT = 1000;
var COVER_DEFAULT_HEIGHT = 300;

var Cover = function Cover(_ref) {
  var _style$color;

  var attributes = _ref.attributes,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      isParentSelected = _ref.isParentSelected,
      onFocus = _ref.onFocus,
      overlayColor = _ref.overlayColor,
      setAttributes = _ref.setAttributes,
      openGeneralSidebar = _ref.openGeneralSidebar,
      closeSettingsBottomSheet = _ref.closeSettingsBottomSheet;
  var backgroundType = attributes.backgroundType,
      dimRatio = attributes.dimRatio,
      focalPoint = attributes.focalPoint,
      minHeight = attributes.minHeight,
      url = attributes.url,
      id = attributes.id,
      style = attributes.style,
      customOverlayColor = attributes.customOverlayColor;
  var CONTAINER_HEIGHT = minHeight || COVER_DEFAULT_HEIGHT;
  var isImage = backgroundType === MEDIA_TYPE_IMAGE;
  var THEME_COLORS_COUNT = 4;
  var colorsDefault = useEditorFeature('color.palette') || [];
  var coverDefaultPalette = {
    colors: colorsDefault.slice(0, THEME_COLORS_COUNT)
  };

  var _experimentalUseGrad = __experimentalUseGradient(),
      gradientValue = _experimentalUseGrad.gradientValue;

  var hasBackground = !!(url || style && style.color && style.color.background || attributes.overlayColor || overlayColor.color || gradientValue);
  var hasOnlyColorBackground = !url && hasBackground;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isCustomColorPickerShowing = _useState2[0],
      setCustomColorPickerShowing = _useState2[1];

  var _useState3 = useState(''),
      _useState4 = _slicedToArray(_useState3, 2),
      customColor = _useState4[0],
      setCustomColor = _useState4[1];

  var openMediaOptionsRef = useRef(); // Used to set a default color for its InnerBlocks
  // since there's no system to inherit styles yet
  // the RichText component will check if there are
  // parent styles for the current block. If there are,
  // it will use that color instead.

  useEffect(function () {
    // While we don't support theme colors
    if (!attributes.overlayColor || !attributes.overlay && url) {
      setAttributes({
        childrenStyles: styles.defaultColor
      });
    }
  }, [setAttributes]); // sync with local media store

  useEffect(mediaUploadSync, []); // initialize uploading flag to false, awaiting sync

  var _useState5 = useState(false),
      _useState6 = _slicedToArray(_useState5, 2),
      isUploadInProgress = _useState6[0],
      setIsUploadInProgress = _useState6[1]; // initialize upload failure flag to true if url is local


  var _useState7 = useState(id && getProtocol(url) === 'file:'),
      _useState8 = _slicedToArray(_useState7, 2),
      didUploadFail = _useState8[0],
      setDidUploadFail = _useState8[1]; // don't show failure if upload is in progress


  var shouldShowFailure = didUploadFail && !isUploadInProgress;

  var onSelectMedia = function onSelectMedia(media) {
    setDidUploadFail(false);
    var onSelect = attributesFromMedia(setAttributes);
    onSelect(media);
  };

  var onHeightChange = function onHeightChange(value) {
    if (minHeight || value !== COVER_DEFAULT_HEIGHT) {
      setAttributes({
        minHeight: value
      });
    }
  };

  var onOpactiyChange = function onOpactiyChange(value) {
    setAttributes({
      dimRatio: value
    });
  };

  var onMediaPressed = function onMediaPressed() {
    if (isUploadInProgress) {
      requestImageUploadCancelDialog(id);
    } else if (shouldShowFailure) {
      requestImageFailedRetryDialog(id);
    } else if (isImage && url) {
      requestImageFullscreenPreview(url);
    }
  };

  var _useState9 = useState(true),
      _useState10 = _slicedToArray(_useState9, 2),
      isVideoLoading = _useState10[0],
      setIsVideoLoading = _useState10[1];

  var onVideoLoadStart = function onVideoLoadStart() {
    setIsVideoLoading(true);
  };

  var onVideoLoad = function onVideoLoad() {
    setIsVideoLoading(false);
  };

  var onClearMedia = function onClearMedia() {
    setAttributes({
      id: undefined,
      url: undefined
    });
    closeSettingsBottomSheet();
  };

  function _setColor(color) {
    setAttributes({
      // clear all related attributes (only one should be set)
      overlayColor: undefined,
      customOverlayColor: color,
      gradient: undefined,
      customGradient: undefined
    });
  }

  function openColorPicker() {
    if (isParentSelected) {
      setCustomColorPickerShowing(true);
      openGeneralSidebar();
    }
  }

  var backgroundColor = getStylesFromColorScheme(styles.backgroundSolid, styles.backgroundSolidDark);
  var overlayStyles = [styles.overlay, url && {
    opacity: dimRatio / 100
  }, !gradientValue && {
    backgroundColor: customOverlayColor || (overlayColor === null || overlayColor === void 0 ? void 0 : overlayColor.color) || (style === null || style === void 0 ? void 0 : (_style$color = style.color) === null || _style$color === void 0 ? void 0 : _style$color.background) || styles.overlay.color
  }, // While we don't support theme colors we add a default bg color
  !overlayColor.color && !url ? backgroundColor : {}, isImage && isParentSelected && !isUploadInProgress && !didUploadFail && styles.overlaySelected];
  var placeholderIconStyle = getStylesFromColorScheme(styles.icon, styles.iconDark);
  var placeholderIcon = createElement(Icon, _extends({
    icon: icon
  }, placeholderIconStyle));

  var toolbarControls = function toolbarControls(open) {
    return createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
      title: __('Edit cover media'),
      icon: replace,
      onClick: open
    })));
  };

  var addMediaButton = function addMediaButton() {
    return createElement(TouchableWithoutFeedback, {
      onPress: openMediaOptionsRef.current
    }, createElement(View, {
      style: styles.selectImageContainer
    }, createElement(View, {
      style: styles.selectImage
    }, createElement(Icon, _extends({
      size: 16,
      icon: image
    }, styles.selectImageIcon)))));
  };

  var controls = createElement(InspectorControls, null, createElement(OverlayColorSettings, {
    attributes: attributes,
    setAttributes: setAttributes
  }), url ? createElement(PanelBody, null, createElement(RangeControl, {
    label: __('Opacity'),
    minimumValue: 0,
    maximumValue: 100,
    value: dimRatio,
    onChange: onOpactiyChange,
    style: styles.rangeCellContainer,
    separatorType: 'topFullWidth'
  })) : null, createElement(PanelBody, {
    title: __('Dimensions')
  }, createElement(RangeControl, {
    label: __('Minimum height in pixels'),
    minimumValue: COVER_MIN_HEIGHT,
    maximumValue: COVER_MAX_HEIGHT,
    value: CONTAINER_HEIGHT,
    onChange: onHeightChange,
    style: styles.rangeCellContainer
  })), url ? createElement(PanelBody, {
    title: __('Media')
  }, createElement(BottomSheet.Cell, {
    leftAlign: true,
    label: __('Clear Media'),
    labelStyle: styles.clearMediaButton,
    onPress: onClearMedia
  })) : null);
  var colorPickerControls = createElement(InspectorControls, null, createElement(BottomSheetConsumer, null, function (_ref2) {
    var shouldEnableBottomSheetScroll = _ref2.shouldEnableBottomSheetScroll,
        shouldEnableBottomSheetMaxHeight = _ref2.shouldEnableBottomSheetMaxHeight,
        onHandleClosingBottomSheet = _ref2.onHandleClosingBottomSheet,
        onHandleHardwareButtonPress = _ref2.onHandleHardwareButtonPress,
        isBottomSheetContentScrolling = _ref2.isBottomSheetContentScrolling;
    return createElement(ColorPicker, {
      shouldEnableBottomSheetScroll: shouldEnableBottomSheetScroll,
      shouldEnableBottomSheetMaxHeight: shouldEnableBottomSheetMaxHeight,
      setColor: function setColor(color) {
        setCustomColor(color);

        _setColor(color);
      },
      onNavigationBack: closeSettingsBottomSheet,
      onHandleClosingBottomSheet: onHandleClosingBottomSheet,
      onHandleHardwareButtonPress: onHandleHardwareButtonPress,
      onBottomSheetClosed: function onBottomSheetClosed() {
        setCustomColorPickerShowing(false);
      },
      isBottomSheetContentScrolling: isBottomSheetContentScrolling,
      bottomLabelText: __('Select a color')
    });
  }));

  var renderContent = function renderContent(getMediaOptions) {
    return createElement(Fragment, null, renderBackground(getMediaOptions), isParentSelected && hasOnlyColorBackground && addMediaButton());
  };

  var renderBackground = function renderBackground(getMediaOptions) {
    return createElement(TouchableWithoutFeedback, {
      accessible: !isParentSelected,
      onPress: onMediaPressed,
      onLongPress: openMediaOptionsRef.current,
      disabled: !isParentSelected
    }, createElement(View, {
      style: [styles.background, backgroundColor]
    }, getMediaOptions(), isParentSelected && backgroundType === VIDEO_BACKGROUND_TYPE && toolbarControls(openMediaOptionsRef.current), createElement(MediaUploadProgress, {
      mediaId: id,
      onUpdateMediaProgress: function onUpdateMediaProgress() {
        setIsUploadInProgress(true);
      },
      onFinishMediaUploadWithSuccess: function onFinishMediaUploadWithSuccess(_ref3) {
        var mediaServerId = _ref3.mediaServerId,
            mediaUrl = _ref3.mediaUrl;
        setIsUploadInProgress(false);
        setDidUploadFail(false);
        setAttributes({
          id: mediaServerId,
          url: mediaUrl,
          backgroundType: backgroundType
        });
      },
      onFinishMediaUploadWithFailure: function onFinishMediaUploadWithFailure() {
        setIsUploadInProgress(false);
        setDidUploadFail(true);
      },
      onMediaUploadStateReset: function onMediaUploadStateReset() {
        setIsUploadInProgress(false);
        setDidUploadFail(false);
        setAttributes({
          id: undefined,
          url: undefined
        });
      }
    }), IMAGE_BACKGROUND_TYPE === backgroundType && createElement(View, {
      style: styles.imageContainer
    }, createElement(Image, {
      editButton: false,
      focalPoint: focalPoint || IMAGE_DEFAULT_FOCAL_POINT,
      isSelected: isParentSelected,
      isUploadFailed: didUploadFail,
      isUploadInProgress: isUploadInProgress,
      onSelectMediaUploadOption: onSelectMedia,
      openMediaOptions: openMediaOptionsRef.current,
      url: url,
      width: styles.image.width
    })), VIDEO_BACKGROUND_TYPE === backgroundType && createElement(Video, {
      muted: true,
      disableFocus: true,
      repeat: true,
      resizeMode: 'cover',
      source: {
        uri: url
      },
      onLoad: onVideoLoad,
      onLoadStart: onVideoLoadStart,
      style: [styles.background, // Hide Video component since it has black background while loading the source
      {
        opacity: isVideoLoading ? 0 : 1
      }]
    })));
  };

  if (!hasBackground || isCustomColorPickerShowing) {
    return createElement(View, null, isCustomColorPickerShowing && colorPickerControls, createElement(MediaPlaceholder, {
      height: styles.mediaPlaceholderEmptyStateContainer.height,
      backgroundColor: customColor,
      hideContent: customColor !== '' && customColor !== undefined,
      icon: placeholderIcon,
      labels: {
        title: __('Cover')
      },
      onSelect: onSelectMedia,
      allowedTypes: ALLOWED_MEDIA_TYPES,
      onFocus: onFocus
    }, createElement(View, {
      style: styles.colorPaletteWrapper
    }, createElement(ColorPalette, {
      customColorIndicatorStyles: styles.paletteColorIndicator,
      customIndicatorWrapperStyles: styles.paletteCustomIndicatorWrapper,
      setColor: _setColor,
      onCustomPress: openColorPicker,
      defaultSettings: coverDefaultPalette,
      shouldShowCustomLabel: false,
      shouldShowCustomVerticalSeparator: false
    }))));
  }

  return createElement(View, {
    style: styles.backgroundContainer
  }, controls, isImage && url && openMediaOptionsRef.current && isParentSelected && !isUploadInProgress && !didUploadFail && createElement(View, {
    style: styles.imageEditButton
  }, createElement(ImageEditingButton, {
    onSelectMediaUploadOption: onSelectMedia,
    openMediaOptions: openMediaOptionsRef.current,
    pickerOptions: [{
      destructiveButton: true,
      id: 'clearMedia',
      label: __('Clear Media'),
      onPress: onClearMedia,
      separated: true,
      value: 'clearMedia'
    }],
    url: url
  })), createElement(View, {
    pointerEvents: "box-none",
    style: [styles.content, {
      minHeight: CONTAINER_HEIGHT
    }]
  }, createElement(InnerBlocks, {
    template: INNER_BLOCKS_TEMPLATE
  })), createElement(View, {
    pointerEvents: "none",
    style: styles.overlayContainer
  }, createElement(View, {
    style: overlayStyles
  }, gradientValue && createElement(Gradient, {
    gradientValue: gradientValue,
    style: styles.background
  }))), createElement(MediaUpload, {
    allowedTypes: ALLOWED_MEDIA_TYPES,
    isReplacingMedia: !hasOnlyColorBackground,
    onSelect: onSelectMedia,
    render: function render(_ref4) {
      var open = _ref4.open,
          getMediaOptions = _ref4.getMediaOptions;
      openMediaOptionsRef.current = open;
      return renderContent(getMediaOptions);
    }
  }), shouldShowFailure && createElement(View, {
    pointerEvents: "none",
    style: styles.uploadFailedContainer
  }, createElement(View, {
    style: styles.uploadFailed
  }, createElement(Icon, _extends({
    icon: warning
  }, styles.uploadFailedIcon)))));
};

export default compose([withColors({
  overlayColor: 'background-color'
}), withSelect(function (select, _ref5) {
  var clientId = _ref5.clientId;

  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var selectedBlockClientId = getSelectedBlockClientId();

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  return {
    settings: getSettings(),
    isParentSelected: selectedBlockClientId === clientId
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch.openGeneralSidebar;

  return {
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    },
    closeSettingsBottomSheet: function closeSettingsBottomSheet() {
      dispatch('core/edit-post').closeGeneralSidebar();
    }
  };
}), withPreferredColorScheme])(Cover);
//# sourceMappingURL=edit.native.js.map