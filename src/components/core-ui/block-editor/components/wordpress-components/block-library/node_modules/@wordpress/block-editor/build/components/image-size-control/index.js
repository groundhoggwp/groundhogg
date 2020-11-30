"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ImageSizeControl;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var IMAGE_SIZE_PRESETS = [25, 50, 75, 100];

function ImageSizeControl(_ref) {
  var _ref2, _ref3;

  var imageWidth = _ref.imageWidth,
      imageHeight = _ref.imageHeight,
      _ref$imageSizeOptions = _ref.imageSizeOptions,
      imageSizeOptions = _ref$imageSizeOptions === void 0 ? [] : _ref$imageSizeOptions,
      _ref$isResizable = _ref.isResizable,
      isResizable = _ref$isResizable === void 0 ? true : _ref$isResizable,
      slug = _ref.slug,
      width = _ref.width,
      height = _ref.height,
      _onChange = _ref.onChange,
      _ref$onChangeImage = _ref.onChangeImage,
      onChangeImage = _ref$onChangeImage === void 0 ? _lodash.noop : _ref$onChangeImage;

  function updateDimensions(nextWidth, nextHeight) {
    return function () {
      _onChange({
        width: nextWidth,
        height: nextHeight
      });
    };
  }

  return (0, _element.createElement)(_element.Fragment, null, !(0, _lodash.isEmpty)(imageSizeOptions) && (0, _element.createElement)(_components.SelectControl, {
    label: (0, _i18n.__)('Image size'),
    value: slug,
    options: imageSizeOptions,
    onChange: onChangeImage
  }), isResizable && (0, _element.createElement)("div", {
    className: "block-editor-image-size-control"
  }, (0, _element.createElement)("p", {
    className: "block-editor-image-size-control__row"
  }, (0, _i18n.__)('Image dimensions')), (0, _element.createElement)("div", {
    className: "block-editor-image-size-control__row"
  }, (0, _element.createElement)(_components.TextControl, {
    type: "number",
    className: "block-editor-image-size-control__width",
    label: (0, _i18n.__)('Width'),
    value: (_ref2 = width !== null && width !== void 0 ? width : imageWidth) !== null && _ref2 !== void 0 ? _ref2 : '',
    min: 1,
    onChange: function onChange(value) {
      return _onChange({
        width: parseInt(value, 10)
      });
    }
  }), (0, _element.createElement)(_components.TextControl, {
    type: "number",
    className: "block-editor-image-size-control__height",
    label: (0, _i18n.__)('Height'),
    value: (_ref3 = height !== null && height !== void 0 ? height : imageHeight) !== null && _ref3 !== void 0 ? _ref3 : '',
    min: 1,
    onChange: function onChange(value) {
      return _onChange({
        height: parseInt(value, 10)
      });
    }
  })), (0, _element.createElement)("div", {
    className: "block-editor-image-size-control__row"
  }, (0, _element.createElement)(_components.ButtonGroup, {
    "aria-label": (0, _i18n.__)('Image size presets')
  }, IMAGE_SIZE_PRESETS.map(function (scale) {
    var scaledWidth = Math.round(imageWidth * (scale / 100));
    var scaledHeight = Math.round(imageHeight * (scale / 100));
    var isCurrent = width === scaledWidth && height === scaledHeight;
    return (0, _element.createElement)(_components.Button, {
      key: scale,
      isSmall: true,
      isPrimary: isCurrent,
      isPressed: isCurrent,
      onClick: updateDimensions(scaledWidth, scaledHeight)
    }, scale, "%");
  })), (0, _element.createElement)(_components.Button, {
    isSmall: true,
    onClick: updateDimensions()
  }, (0, _i18n.__)('Reset')))));
}
//# sourceMappingURL=index.js.map