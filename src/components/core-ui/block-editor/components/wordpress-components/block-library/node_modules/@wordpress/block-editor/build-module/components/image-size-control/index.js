import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { isEmpty, noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { Button, ButtonGroup, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
var IMAGE_SIZE_PRESETS = [25, 50, 75, 100];
export default function ImageSizeControl(_ref) {
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
      onChangeImage = _ref$onChangeImage === void 0 ? noop : _ref$onChangeImage;

  function updateDimensions(nextWidth, nextHeight) {
    return function () {
      _onChange({
        width: nextWidth,
        height: nextHeight
      });
    };
  }

  return createElement(Fragment, null, !isEmpty(imageSizeOptions) && createElement(SelectControl, {
    label: __('Image size'),
    value: slug,
    options: imageSizeOptions,
    onChange: onChangeImage
  }), isResizable && createElement("div", {
    className: "block-editor-image-size-control"
  }, createElement("p", {
    className: "block-editor-image-size-control__row"
  }, __('Image dimensions')), createElement("div", {
    className: "block-editor-image-size-control__row"
  }, createElement(TextControl, {
    type: "number",
    className: "block-editor-image-size-control__width",
    label: __('Width'),
    value: (_ref2 = width !== null && width !== void 0 ? width : imageWidth) !== null && _ref2 !== void 0 ? _ref2 : '',
    min: 1,
    onChange: function onChange(value) {
      return _onChange({
        width: parseInt(value, 10)
      });
    }
  }), createElement(TextControl, {
    type: "number",
    className: "block-editor-image-size-control__height",
    label: __('Height'),
    value: (_ref3 = height !== null && height !== void 0 ? height : imageHeight) !== null && _ref3 !== void 0 ? _ref3 : '',
    min: 1,
    onChange: function onChange(value) {
      return _onChange({
        height: parseInt(value, 10)
      });
    }
  })), createElement("div", {
    className: "block-editor-image-size-control__row"
  }, createElement(ButtonGroup, {
    "aria-label": __('Image size presets')
  }, IMAGE_SIZE_PRESETS.map(function (scale) {
    var scaledWidth = Math.round(imageWidth * (scale / 100));
    var scaledHeight = Math.round(imageHeight * (scale / 100));
    var isCurrent = width === scaledWidth && height === scaledHeight;
    return createElement(Button, {
      key: scale,
      isSmall: true,
      isPrimary: isCurrent,
      isPressed: isCurrent,
      onClick: updateDimensions(scaledWidth, scaledHeight)
    }, scale, "%");
  })), createElement(Button, {
    isSmall: true,
    onClick: updateDimensions()
  }, __('Reset')))));
}
//# sourceMappingURL=index.js.map