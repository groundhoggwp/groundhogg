"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _fastAverageColor = _interopRequireDefault(require("fast-average-color"));

var _tinycolor = _interopRequireDefault(require("tinycolor2"));

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _shared = require("./shared");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Module Constants
 */
var ALLOWED_MEDIA_TYPES = ['image', 'video'];
var INNER_BLOCKS_TEMPLATE = [['core/paragraph', {
  align: 'center',
  fontSize: 'large',
  placeholder: (0, _i18n.__)('Write title…')
}]];
var BoxControlVisualizer = _components.__experimentalBoxControl.__Visualizer;

function retrieveFastAverageColor() {
  if (!retrieveFastAverageColor.fastAverageColor) {
    retrieveFastAverageColor.fastAverageColor = new _fastAverageColor.default();
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

  var _useState = (0, _element.useState)(null),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      temporaryInput = _useState2[0],
      setTemporaryInput = _useState2[1];

  var instanceId = (0, _compose.useInstanceId)(_blockEditor.__experimentalUnitControl);
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
  var min = isPx ? _shared.COVER_MIN_HEIGHT : 0;
  return (0, _element.createElement)(_components.BaseControl, {
    label: (0, _i18n.__)('Minimum height of cover'),
    id: inputId
  }, (0, _element.createElement)(_blockEditor.__experimentalUnitControl, {
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
    units: _shared.CSS_UNITS,
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
      props = (0, _objectWithoutProperties2.default)(_ref2, ["className", "onResizeStart", "onResize", "onResizeStop"]);

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isResizing = _useState4[0],
      setIsResizing = _useState4[1];

  return (0, _element.createElement)(_components.ResizableBox, (0, _extends2.default)({
    className: (0, _classnames2.default)(className, {
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
    minHeight: _shared.COVER_MIN_HEIGHT
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

  var _useState5 = (0, _element.useState)(false),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      isDark = _useState6[0],
      setIsDark = _useState6[1];

  (0, _element.useEffect)(function () {
    // If opacity is lower than 50 the dominant color is the image or video color,
    // so use that color for the dark mode computation.
    if (url && dimRatio <= 50 && elementRef.current) {
      retrieveFastAverageColor().getColorAsync(elementRef.current, function (color) {
        setIsDark(color.isDark);
      });
    }
  }, [url, url && dimRatio <= 50 && elementRef.current, setIsDark]);
  (0, _element.useEffect)(function () {
    // If opacity is greater than 50 the dominant color is the overlay color,
    // so use that color for the dark mode computation.
    if (dimRatio > 50 || !url) {
      if (!overlayColor) {
        // If no overlay color exists the overlay color is black (isDark )
        setIsDark(true);
        return;
      }

      setIsDark((0, _tinycolor.default)(overlayColor).isDark());
    }
  }, [overlayColor, dimRatio > 50 || !url, setIsDark]);
  (0, _element.useEffect)(function () {
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

  var _experimentalUseGrad = (0, _blockEditor.__experimentalUseGradient)(),
      gradientClass = _experimentalUseGrad.gradientClass,
      gradientValue = _experimentalUseGrad.gradientValue,
      setGradient = _experimentalUseGrad.setGradient;

  var onSelectMedia = (0, _shared.attributesFromMedia)(setAttributes);

  var toggleParallax = function toggleParallax() {
    setAttributes(_objectSpread({
      hasParallax: !hasParallax
    }, !hasParallax ? {
      focalPoint: undefined
    } : {}));
  };

  var isDarkElement = (0, _element.useRef)();
  var isDark = useCoverIsDark(url, dimRatio, overlayColor.color, isDarkElement);
  var isImageBackground = _shared.IMAGE_BACKGROUND_TYPE === backgroundType;
  var isVideoBackground = _shared.VIDEO_BACKGROUND_TYPE === backgroundType;

  var _useState7 = (0, _element.useState)(null),
      _useState8 = (0, _slicedToArray2.default)(_useState7, 2),
      temporaryMinHeight = _useState8[0],
      setTemporaryMinHeight = _useState8[1];

  var removeAllNotices = noticeOperations.removeAllNotices,
      createErrorNotice = noticeOperations.createErrorNotice;
  var minHeightWithUnit = minHeightUnit ? "".concat(minHeight).concat(minHeightUnit) : minHeight;

  var style = _objectSpread(_objectSpread({}, isImageBackground ? (0, _shared.backgroundImageStyles)(url) : {}), {}, {
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
  var controls = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, hasBackground && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.__experimentalBlockAlignmentMatrixToolbar, {
    label: (0, _i18n.__)('Change content position'),
    value: contentPosition,
    onChange: function onChange(nextPosition) {
      return setAttributes({
        contentPosition: nextPosition
      });
    }
  }), (0, _element.createElement)(_blockEditor.MediaReplaceFlow, {
    mediaId: id,
    mediaURL: url,
    allowedTypes: ALLOWED_MEDIA_TYPES,
    accept: "image/*,video/*",
    onSelect: onSelectMedia
  }))), (0, _element.createElement)(_blockEditor.InspectorControls, null, !!url && (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Media settings')
  }, isImageBackground && (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Fixed background'),
    checked: hasParallax,
    onChange: toggleParallax
  }), showFocalPointPicker && (0, _element.createElement)(_components.FocalPointPicker, {
    label: (0, _i18n.__)('Focal point picker'),
    url: url,
    value: focalPoint,
    onChange: function onChange(newFocalPoint) {
      return setAttributes({
        focalPoint: newFocalPoint
      });
    }
  }), (0, _element.createElement)(_components.PanelRow, null, (0, _element.createElement)(_components.Button, {
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
  }, (0, _i18n.__)('Clear Media')))), hasBackground && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Dimensions')
  }, (0, _element.createElement)(CoverHeightInput, {
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
  })), (0, _element.createElement)(_blockEditor.__experimentalPanelColorGradientSettings, {
    title: (0, _i18n.__)('Overlay'),
    initialOpen: true,
    settings: [{
      colorValue: overlayColor.color,
      gradientValue: gradientValue,
      onColorChange: setOverlayColor,
      onGradientChange: setGradient,
      label: (0, _i18n.__)('Color')
    }]
  }, !!url && (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Opacity'),
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
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();

  if (!hasBackground) {
    var placeholderIcon = (0, _element.createElement)(_blockEditor.BlockIcon, {
      icon: _icons.cover
    });
    var label = (0, _i18n.__)('Cover');
    return (0, _element.createElement)(_element.Fragment, null, controls, (0, _element.createElement)("div", (0, _extends2.default)({}, blockWrapperProps, {
      className: (0, _classnames2.default)('is-placeholder', blockWrapperProps.className)
    }), (0, _element.createElement)(_blockEditor.MediaPlaceholder, {
      icon: placeholderIcon,
      labels: {
        title: label,
        instructions: (0, _i18n.__)('Upload an image or video file, or pick one from your media library.')
      },
      onSelect: onSelectMedia,
      accept: "image/*,video/*",
      allowedTypes: ALLOWED_MEDIA_TYPES,
      notices: noticeUI,
      onError: function onError(message) {
        removeAllNotices();
        createErrorNotice(message);
      }
    }, (0, _element.createElement)("div", {
      className: "wp-block-cover__placeholder-background-options"
    }, (0, _element.createElement)(_blockEditor.ColorPalette, {
      disableCustomColors: true,
      value: overlayColor.color,
      onChange: setOverlayColor,
      clearable: false
    })))));
  }

  var classes = (0, _classnames2.default)((0, _shared.dimRatioToClass)(dimRatio), (_classnames = {
    'is-dark-theme': isDark,
    'has-background-dim': dimRatio !== 0,
    'has-parallax': hasParallax
  }, (0, _defineProperty2.default)(_classnames, overlayColor.class, overlayColor.class), (0, _defineProperty2.default)(_classnames, 'has-background-gradient', gradientValue), (0, _defineProperty2.default)(_classnames, gradientClass, !url && gradientClass), (0, _defineProperty2.default)(_classnames, 'has-custom-content-position', !(0, _shared.isContentPositionCenter)(contentPosition)), _classnames), (0, _shared.getPositionClassName)(contentPosition));
  return (0, _element.createElement)(_element.Fragment, null, controls, (0, _element.createElement)("div", (0, _extends2.default)({}, blockWrapperProps, {
    className: (0, _classnames2.default)(classes, blockWrapperProps.className),
    style: _objectSpread(_objectSpread({}, style), blockWrapperProps.style),
    "data-url": url
  }), (0, _element.createElement)(BoxControlVisualizer, {
    values: styleAttribute === null || styleAttribute === void 0 ? void 0 : (_styleAttribute$spaci = styleAttribute.spacing) === null || _styleAttribute$spaci === void 0 ? void 0 : _styleAttribute$spaci.padding,
    showValues: styleAttribute === null || styleAttribute === void 0 ? void 0 : (_styleAttribute$visua = styleAttribute.visualizers) === null || _styleAttribute$visua === void 0 ? void 0 : _styleAttribute$visua.padding
  }), (0, _element.createElement)(ResizableCover, {
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
  (0, _element.createElement)("img", {
    ref: isDarkElement,
    "aria-hidden": true,
    alt: "",
    style: {
      display: 'none'
    },
    src: url
  }), url && gradientValue && dimRatio !== 0 && (0, _element.createElement)("span", {
    "aria-hidden": "true",
    className: (0, _classnames2.default)('wp-block-cover__gradient-background', gradientClass),
    style: {
      background: gradientValue
    }
  }), isVideoBackground && (0, _element.createElement)("video", {
    ref: isDarkElement,
    className: "wp-block-cover__video-background",
    autoPlay: true,
    muted: true,
    loop: true,
    src: url,
    style: {
      objectPosition: positionValue
    }
  }), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-cover__inner-container'
    },
    template: INNER_BLOCKS_TEMPLATE
  })));
}

var _default = (0, _compose.compose)([(0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      toggleSelection = _dispatch.toggleSelection;

  return {
    toggleSelection: toggleSelection
  };
}), (0, _blockEditor.withColors)({
  overlayColor: 'background-color'
}), _components.withNotices, _compose.withInstanceId])(CoverEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map