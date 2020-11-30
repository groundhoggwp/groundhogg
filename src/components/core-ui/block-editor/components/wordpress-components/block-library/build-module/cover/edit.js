import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import FastAverageColor from 'fast-average-color';
import tinycolor from 'tinycolor2';
/**
 * WordPress dependencies
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { BaseControl, Button, FocalPointPicker, PanelBody, PanelRow, RangeControl, ResizableBox, ToggleControl, withNotices, __experimentalBoxControl as BoxControl } from '@wordpress/components';
import { compose, withInstanceId, useInstanceId } from '@wordpress/compose';
import { BlockControls, BlockIcon, InnerBlocks, InspectorControls, MediaPlaceholder, MediaReplaceFlow, withColors, ColorPalette, __experimentalUseBlockWrapperProps as useBlockWrapperProps, __experimentalUseGradient, __experimentalPanelColorGradientSettings as PanelColorGradientSettings, __experimentalUnitControl as UnitControl, __experimentalBlockAlignmentMatrixToolbar as BlockAlignmentMatrixToolbar } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { withDispatch } from '@wordpress/data';
import { cover as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { attributesFromMedia, IMAGE_BACKGROUND_TYPE, VIDEO_BACKGROUND_TYPE, COVER_MIN_HEIGHT, CSS_UNITS, backgroundImageStyles, dimRatioToClass, isContentPositionCenter, getPositionClassName } from './shared';
/**
 * Module Constants
 */

var ALLOWED_MEDIA_TYPES = ['image', 'video'];
var INNER_BLOCKS_TEMPLATE = [['core/paragraph', {
  align: 'center',
  fontSize: 'large',
  placeholder: __('Write title…')
}]];
var BoxControlVisualizer = BoxControl.__Visualizer;

function retrieveFastAverageColor() {
  if (!retrieveFastAverageColor.fastAverageColor) {
    retrieveFastAverageColor.fastAverageColor = new FastAverageColor();
  }

  return retrieveFastAverageColor.fastAverageColor;
}

function CoverHeightInput(_ref) {
  var onChange = _ref.onChange,
      onUnitChange = _ref.onUnitChange,
      _ref$unit = _ref.unit,
      unit = _ref$unit === void 0 ? 'px' : _ref$unit,
      _ref$value = _ref.value,
      value = _ref$value === void 0 ? '' : _ref$value;

  var _useState = useState(null),
      _useState2 = _slicedToArray(_useState, 2),
      temporaryInput = _useState2[0],
      setTemporaryInput = _useState2[1];

  var instanceId = useInstanceId(UnitControl);
  var inputId = "block-cover-height-input-".concat(instanceId);
  var isPx = unit === 'px';

  var handleOnChange = function handleOnChange(unprocessedValue) {
    var inputValue = unprocessedValue !== '' ? parseInt(unprocessedValue, 10) : undefined;

    if (isNaN(inputValue) && inputValue !== undefined) {
      setTemporaryInput(unprocessedValue);
      return;
    }

    setTemporaryInput(null);
    onChange(inputValue);

    if (inputValue === undefined) {
      onUnitChange();
    }
  };

  var handleOnBlur = function handleOnBlur() {
    if (temporaryInput !== null) {
      setTemporaryInput(null);
    }
  };

  var inputValue = temporaryInput !== null ? temporaryInput : value;
  var min = isPx ? COVER_MIN_HEIGHT : 0;
  return createElement(BaseControl, {
    label: __('Minimum height of cover'),
    id: inputId
  }, createElement(UnitControl, {
    id: inputId,
    isResetValueOnUnitChange: true,
    min: min,
    onBlur: handleOnBlur,
    onChange: handleOnChange,
    onUnitChange: onUnitChange,
    step: "1",
    style: {
      maxWidth: 80
    },
    unit: unit,
    units: CSS_UNITS,
    value: inputValue
  }));
}

var RESIZABLE_BOX_ENABLE_OPTION = {
  top: false,
  right: false,
  bottom: true,
  left: false,
  topRight: false,
  bottomRight: false,
  bottomLeft: false,
  topLeft: false
};

function ResizableCover(_ref2) {
  var className = _ref2.className,
      _onResizeStart = _ref2.onResizeStart,
      _onResize = _ref2.onResize,
      _onResizeStop = _ref2.onResizeStop,
      props = _objectWithoutProperties(_ref2, ["className", "onResizeStart", "onResize", "onResizeStop"]);

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isResizing = _useState4[0],
      setIsResizing = _useState4[1];

  return createElement(ResizableBox, _extends({
    className: classnames(className, {
      'is-resizing': isResizing
    }),
    enable: RESIZABLE_BOX_ENABLE_OPTION,
    onResizeStart: function onResizeStart(_event, _direction, elt) {
      _onResizeStart(elt.clientHeight);

      _onResize(elt.clientHeight);
    },
    onResize: function onResize(_event, _direction, elt) {
      _onResize(elt.clientHeight);

      if (!isResizing) {
        setIsResizing(true);
      }
    },
    onResizeStop: function onResizeStop(_event, _direction, elt) {
      _onResizeStop(elt.clientHeight);

      setIsResizing(false);
    },
    minHeight: COVER_MIN_HEIGHT
  }, props));
}
/**
 * useCoverIsDark is a hook that returns a boolean variable specifying if the cover
 * background is dark or not.
 *
 * @param {?string} url          Url of the media background.
 * @param {?number} dimRatio     Transparency of the overlay color. If an image and
 *                               color are set, dimRatio is used to decide what is used
 *                               for background darkness checking purposes.
 * @param {?string} overlayColor String containing the overlay color value if one exists.
 * @param {?Object} elementRef   If a media background is set, elementRef should contain a reference to a
 *                               dom element that renders that media.
 *
 * @return {boolean} True if the cover background is considered "dark" and false otherwise.
 */


function useCoverIsDark(url) {
  var dimRatio = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 50;
  var overlayColor = arguments.length > 2 ? arguments[2] : undefined;
  var elementRef = arguments.length > 3 ? arguments[3] : undefined;

  var _useState5 = useState(false),
      _useState6 = _slicedToArray(_useState5, 2),
      isDark = _useState6[0],
      setIsDark = _useState6[1];

  useEffect(function () {
    // If opacity is lower than 50 the dominant color is the image or video color,
    // so use that color for the dark mode computation.
    if (url && dimRatio <= 50 && elementRef.current) {
      retrieveFastAverageColor().getColorAsync(elementRef.current, function (color) {
        setIsDark(color.isDark);
      });
    }
  }, [url, url && dimRatio <= 50 && elementRef.current, setIsDark]);
  useEffect(function () {
    // If opacity is greater than 50 the dominant color is the overlay color,
    // so use that color for the dark mode computation.
    if (dimRatio > 50 || !url) {
      if (!overlayColor) {
        // If no overlay color exists the overlay color is black (isDark )
        setIsDark(true);
        return;
      }

      setIsDark(tinycolor(overlayColor).isDark());
    }
  }, [overlayColor, dimRatio > 50 || !url, setIsDark]);
  useEffect(function () {
    if (!url && !overlayColor) {
      // Reset isDark
      setIsDark(false);
    }
  }, [!url && !overlayColor, setIsDark]);
  return isDark;
}

function CoverEdit(_ref3) {
  var _classnames, _styleAttribute$spaci, _styleAttribute$visua;

  var attributes = _ref3.attributes,
      setAttributes = _ref3.setAttributes,
      isSelected = _ref3.isSelected,
      noticeUI = _ref3.noticeUI,
      overlayColor = _ref3.overlayColor,
      setOverlayColor = _ref3.setOverlayColor,
      toggleSelection = _ref3.toggleSelection,
      noticeOperations = _ref3.noticeOperations;
  var contentPosition = attributes.contentPosition,
      id = attributes.id,
      backgroundType = attributes.backgroundType,
      dimRatio = attributes.dimRatio,
      focalPoint = attributes.focalPoint,
      hasParallax = attributes.hasParallax,
      minHeight = attributes.minHeight,
      minHeightUnit = attributes.minHeightUnit,
      styleAttribute = attributes.style,
      url = attributes.url;

  var _experimentalUseGrad = __experimentalUseGradient(),
      gradientClass = _experimentalUseGrad.gradientClass,
      gradientValue = _experimentalUseGrad.gradientValue,
      setGradient = _experimentalUseGrad.setGradient;

  var onSelectMedia = attributesFromMedia(setAttributes);

  var toggleParallax = function toggleParallax() {
    setAttributes(_objectSpread({
      hasParallax: !hasParallax
    }, !hasParallax ? {
      focalPoint: undefined
    } : {}));
  };

  var isDarkElement = useRef();
  var isDark = useCoverIsDark(url, dimRatio, overlayColor.color, isDarkElement);
  var isImageBackground = IMAGE_BACKGROUND_TYPE === backgroundType;
  var isVideoBackground = VIDEO_BACKGROUND_TYPE === backgroundType;

  var _useState7 = useState(null),
      _useState8 = _slicedToArray(_useState7, 2),
      temporaryMinHeight = _useState8[0],
      setTemporaryMinHeight = _useState8[1];

  var removeAllNotices = noticeOperations.removeAllNotices,
      createErrorNotice = noticeOperations.createErrorNotice;
  var minHeightWithUnit = minHeightUnit ? "".concat(minHeight).concat(minHeightUnit) : minHeight;

  var style = _objectSpread(_objectSpread({}, isImageBackground ? backgroundImageStyles(url) : {}), {}, {
    backgroundColor: overlayColor.color,
    minHeight: temporaryMinHeight || minHeightWithUnit || undefined
  });

  if (gradientValue && !url) {
    style.background = gradientValue;
  }

  var positionValue;

  if (focalPoint) {
    positionValue = "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%");

    if (isImageBackground) {
      style.backgroundPosition = positionValue;
    }
  }

  var hasBackground = !!(url || overlayColor.color || gradientValue);
  var showFocalPointPicker = isVideoBackground || isImageBackground && !hasParallax;
  var controls = createElement(Fragment, null, createElement(BlockControls, null, hasBackground && createElement(Fragment, null, createElement(BlockAlignmentMatrixToolbar, {
    label: __('Change content position'),
    value: contentPosition,
    onChange: function onChange(nextPosition) {
      return setAttributes({
        contentPosition: nextPosition
      });
    }
  }), createElement(MediaReplaceFlow, {
    mediaId: id,
    mediaURL: url,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "image/*,video/*",
    onSelect: onSelectMedia
  }))), createElement(InspectorControls, null, !!url && createElement(PanelBody, {
    title: __('Media settings')
  }, isImageBackground && createElement(ToggleControl, {
    label: __('Fixed background'),
    checked: hasParallax,
    onChange: toggleParallax
  }), showFocalPointPicker && createElement(FocalPointPicker, {
    label: __('Focal point picker'),
    url: url,
    value: focalPoint,
    onChange: function onChange(newFocalPoint) {
      return setAttributes({
        focalPoint: newFocalPoint
      });
    }
  }), createElement(PanelRow, null, createElement(Button, {
    isSecondary: true,
    isSmall: true,
    className: "block-library-cover__reset-button",
    onClick: function onClick() {
      return setAttributes({
        url: undefined,
        id: undefined,
        backgroundType: undefined,
        dimRatio: undefined,
        focalPoint: undefined,
        hasParallax: undefined
      });
    }
  }, __('Clear Media')))), hasBackground && createElement(Fragment, null, createElement(PanelBody, {
    title: __('Dimensions')
  }, createElement(CoverHeightInput, {
    value: temporaryMinHeight || minHeight,
    unit: minHeightUnit,
    onChange: function onChange(newMinHeight) {
      return setAttributes({
        minHeight: newMinHeight
      });
    },
    onUnitChange: function onUnitChange(nextUnit) {
      setAttributes({
        minHeightUnit: nextUnit
      });
    }
  })), createElement(PanelColorGradientSettings, {
    title: __('Overlay'),
    initialOpen: true,
    settings: [{
      colorValue: overlayColor.color,
      gradientValue: gradientValue,
      onColorChange: setOverlayColor,
      onGradientChange: setGradient,
      label: __('Color')
    }]
  }, !!url && createElement(RangeControl, {
    label: __('Opacity'),
    value: dimRatio,
    onChange: function onChange(newDimRation) {
      return setAttributes({
        dimRatio: newDimRation
      });
    },
    min: 0,
    max: 100,
    required: true
  })))));
  var blockWrapperProps = useBlockWrapperProps();

  if (!hasBackground) {
    var placeholderIcon = createElement(BlockIcon, {
      icon: icon
    });

    var label = __('Cover');

    return createElement(Fragment, null, controls, createElement("div", _extends({}, blockWrapperProps, {
      className: classnames('is-placeholder', blockWrapperProps.className)
    }), createElement(MediaPlaceholder, {
      icon: placeholderIcon,
      labels: {
        title: label,
        instructions: __('Upload an image or video file, or pick one from your media library.')
      },
      onSelect: onSelectMedia,
      accept: "image/*,video/*",
      allowedTypes: ALLOWED_MEDIA_TYPES,
      notices: noticeUI,
      onError: function onError(message) {
        removeAllNotices();
        createErrorNotice(message);
      }
    }, createElement("div", {
      className: "wp-block-cover__placeholder-background-options"
    }, createElement(ColorPalette, {
      disableCustomColors: true,
      value: overlayColor.color,
      onChange: setOverlayColor,
      clearable: false
    })))));
  }

  var classes = classnames(dimRatioToClass(dimRatio), (_classnames = {
    'is-dark-theme': isDark,
    'has-background-dim': dimRatio !== 0,
    'has-parallax': hasParallax
  }, _defineProperty(_classnames, overlayColor.class, overlayColor.class), _defineProperty(_classnames, 'has-background-gradient', gradientValue), _defineProperty(_classnames, gradientClass, !url && gradientClass), _defineProperty(_classnames, 'has-custom-content-position', !isContentPositionCenter(contentPosition)), _classnames), getPositionClassName(contentPosition));
  return createElement(Fragment, null, controls, createElement("div", _extends({}, blockWrapperProps, {
    className: classnames(classes, blockWrapperProps.className),
    style: _objectSpread(_objectSpread({}, style), blockWrapperProps.style),
    "data-url": url
  }), createElement(BoxControlVisualizer, {
    values: styleAttribute === null || styleAttribute === void 0 ? void 0 : (_styleAttribute$spaci = styleAttribute.spacing) === null || _styleAttribute$spaci === void 0 ? void 0 : _styleAttribute$spaci.padding,
    showValues: styleAttribute === null || styleAttribute === void 0 ? void 0 : (_styleAttribute$visua = styleAttribute.visualizers) === null || _styleAttribute$visua === void 0 ? void 0 : _styleAttribute$visua.padding
  }), createElement(ResizableCover, {
    className: "block-library-cover__resize-container",
    onResizeStart: function onResizeStart() {
      setAttributes({
        minHeightUnit: 'px'
      });
      toggleSelection(false);
    },
    onResize: setTemporaryMinHeight,
    onResizeStop: function onResizeStop(newMinHeight) {
      toggleSelection(true);
      setAttributes({
        minHeight: newMinHeight
      });
      setTemporaryMinHeight(null);
    },
    showHandle: isSelected
  }), isImageBackground && // Used only to programmatically check if the image is dark or not
  createElement("img", {
    ref: isDarkElement,
    "aria-hidden": true,
    alt: "",
    style: {
      display: 'none'
    },
    src: url
  }), url && gradientValue && dimRatio !== 0 && createElement("span", {
    "aria-hidden": "true",
    className: classnames('wp-block-cover__gradient-background', gradientClass),
    style: {
      background: gradientValue
    }
  }), isVideoBackground && createElement("video", {
    ref: isDarkElement,
    className: "wp-block-cover__video-background",
    autoPlay: true,
    muted: true,
    loop: true,
    src: url,
    style: {
      objectPosition: positionValue
    }
  }), createElement(InnerBlocks, {
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-cover__inner-container'
    },
    template: INNER_BLOCKS_TEMPLATE
  })));
}

export default compose([withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      toggleSelection = _dispatch.toggleSelection;

  return {
    toggleSelection: toggleSelection
  };
}), withColors({
  overlayColor: 'background-color'
}), withNotices, withInstanceId])(CoverEdit);
//# sourceMappingURL=edit.js.map