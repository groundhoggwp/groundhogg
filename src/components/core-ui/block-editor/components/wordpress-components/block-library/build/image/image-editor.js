"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ImageEditor;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactEasyCrop = _interopRequireDefault(require("react-easy-crop"));

var _classnames = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _icons = require("@wordpress/icons");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _apiFetch = _interopRequireDefault(require("@wordpress/api-fetch"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var MIN_ZOOM = 100;
var MAX_ZOOM = 300;
var POPOVER_PROPS = {
  position: 'bottom right',
  isAlternate: true
};

function AspectGroup(_ref) {
  var aspectRatios = _ref.aspectRatios,
      isDisabled = _ref.isDisabled,
      label = _ref.label,
      _onClick = _ref.onClick,
      value = _ref.value;
  return (0, _element.createElement)(_components.MenuGroup, {
    label: label
  }, aspectRatios.map(function (_ref2) {
    var title = _ref2.title,
        aspect = _ref2.aspect;
    return (0, _element.createElement)(_components.MenuItem, {
      key: aspect,
      disabled: isDisabled,
      onClick: function onClick() {
        _onClick(aspect);
      },
      role: "menuitemradio",
      isSelected: aspect === value,
      icon: aspect === value ? _icons.check : undefined
    }, title);
  }));
}

function AspectMenu(_ref3) {
  var toggleProps = _ref3.toggleProps,
      isDisabled = _ref3.isDisabled,
      _onClick2 = _ref3.onClick,
      value = _ref3.value,
      defaultValue = _ref3.defaultValue;
  return (0, _element.createElement)(_components.DropdownMenu, {
    icon: _icons.aspectRatio,
    label: (0, _i18n.__)('Aspect Ratio'),
    popoverProps: POPOVER_PROPS,
    toggleProps: toggleProps,
    className: "wp-block-image__aspect-ratio"
  }, function (_ref4) {
    var onClose = _ref4.onClose;
    return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(AspectGroup, {
      isDisabled: isDisabled,
      onClick: function onClick(aspect) {
        _onClick2(aspect);

        onClose();
      },
      value: value,
      aspectRatios: [{
        title: (0, _i18n.__)('Original'),
        aspect: defaultValue
      }, {
        title: (0, _i18n.__)('Square'),
        aspect: 1
      }]
    }), (0, _element.createElement)(AspectGroup, {
      label: (0, _i18n.__)('Landscape'),
      isDisabled: isDisabled,
      onClick: function onClick(aspect) {
        _onClick2(aspect);

        onClose();
      },
      value: value,
      aspectRatios: [{
        title: (0, _i18n.__)('16:10'),
        aspect: 16 / 10
      }, {
        title: (0, _i18n.__)('16:9'),
        aspect: 16 / 9
      }, {
        title: (0, _i18n.__)('4:3'),
        aspect: 4 / 3
      }, {
        title: (0, _i18n.__)('3:2'),
        aspect: 3 / 2
      }]
    }), (0, _element.createElement)(AspectGroup, {
      label: (0, _i18n.__)('Portrait'),
      isDisabled: isDisabled,
      onClick: function onClick(aspect) {
        _onClick2(aspect);

        onClose();
      },
      value: value,
      aspectRatios: [{
        title: (0, _i18n.__)('10:16'),
        aspect: 10 / 16
      }, {
        title: (0, _i18n.__)('9:16'),
        aspect: 9 / 16
      }, {
        title: (0, _i18n.__)('3:4'),
        aspect: 3 / 4
      }, {
        title: (0, _i18n.__)('2:3'),
        aspect: 2 / 3
      }]
    }));
  });
}

function ImageEditor(_ref5) {
  var id = _ref5.id,
      url = _ref5.url,
      setAttributes = _ref5.setAttributes,
      naturalWidth = _ref5.naturalWidth,
      naturalHeight = _ref5.naturalHeight,
      width = _ref5.width,
      height = _ref5.height,
      clientWidth = _ref5.clientWidth,
      setIsEditingImage = _ref5.setIsEditingImage;

  var _useDispatch = (0, _data.useDispatch)('core/notices'),
      createErrorNotice = _useDispatch.createErrorNotice;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      inProgress = _useState2[0],
      setIsProgress = _useState2[1];

  var _useState3 = (0, _element.useState)(null),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      crop = _useState4[0],
      setCrop = _useState4[1];

  var _useState5 = (0, _element.useState)({
    x: 0,
    y: 0
  }),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      position = _useState6[0],
      setPosition = _useState6[1];

  var _useState7 = (0, _element.useState)(100),
      _useState8 = (0, _slicedToArray2.default)(_useState7, 2),
      zoom = _useState8[0],
      setZoom = _useState8[1];

  var _useState9 = (0, _element.useState)(naturalWidth / naturalHeight),
      _useState10 = (0, _slicedToArray2.default)(_useState9, 2),
      aspect = _useState10[0],
      setAspect = _useState10[1];

  var _useState11 = (0, _element.useState)(0),
      _useState12 = (0, _slicedToArray2.default)(_useState11, 2),
      rotation = _useState12[0],
      setRotation = _useState12[1];

  var _useState13 = (0, _element.useState)(),
      _useState14 = (0, _slicedToArray2.default)(_useState13, 2),
      editedUrl = _useState14[0],
      setEditedUrl = _useState14[1];

  var editedWidth = width;
  var editedHeight = height || clientWidth * naturalHeight / naturalWidth;
  var naturalAspectRatio = naturalWidth / naturalHeight;

  if (rotation % 180 === 90) {
    editedHeight = clientWidth * naturalWidth / naturalHeight;
    naturalAspectRatio = naturalHeight / naturalWidth;
  }

  function apply() {
    setIsProgress(true);
    var attrs = {}; // The crop script may return some very small, sub-pixel values when the image was not cropped.
    // Crop only when the new size has changed by more than 0.1%.

    if (crop.width < 99.9 || crop.height < 99.9) {
      attrs = crop;
    }

    if (rotation > 0) {
      attrs.rotation = rotation;
    }

    attrs.src = url;
    (0, _apiFetch.default)({
      path: "/wp/v2/media/".concat(id, "/edit"),
      method: 'POST',
      data: attrs
    }).then(function (response) {
      setAttributes({
        id: response.id,
        url: response.source_url,
        height: height && width ? width / aspect : undefined
      });
    }).catch(function (error) {
      createErrorNotice((0, _i18n.sprintf)(
      /* translators: 1. Error message */
      (0, _i18n.__)('Could not edit image. %s'), error.message), {
        id: 'image-editing-error',
        type: 'snackbar'
      });
    }).finally(function () {
      setIsProgress(false);
      setIsEditingImage(false);
    });
  }

  function rotate() {
    var angle = (rotation + 90) % 360;

    if (angle === 0) {
      setEditedUrl();
      setRotation(angle);
      setAspect(1 / aspect);
      setPosition({
        x: -(position.y * naturalAspectRatio),
        y: position.x * naturalAspectRatio
      });
      return;
    }

    function editImage(event) {
      var canvas = document.createElement('canvas');
      var translateX = 0;
      var translateY = 0;

      if (angle % 180) {
        canvas.width = event.target.height;
        canvas.height = event.target.width;
      } else {
        canvas.width = event.target.width;
        canvas.height = event.target.height;
      }

      if (angle === 90 || angle === 180) {
        translateX = canvas.width;
      }

      if (angle === 270 || angle === 180) {
        translateY = canvas.height;
      }

      var context = canvas.getContext('2d');
      context.translate(translateX, translateY);
      context.rotate(angle * Math.PI / 180);
      context.drawImage(event.target, 0, 0);
      canvas.toBlob(function (blob) {
        setEditedUrl(URL.createObjectURL(blob));
        setRotation(angle);
        setAspect(1 / aspect);
        setPosition({
          x: -(position.y * naturalAspectRatio),
          y: position.x * naturalAspectRatio
        });
      });
    }

    var el = new window.Image();
    el.src = url;
    el.onload = editImage;
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: (0, _classnames.default)('wp-block-image__crop-area', {
      'is-applying': inProgress
    }),
    style: {
      width: editedWidth,
      height: editedHeight
    }
  }, (0, _element.createElement)(_reactEasyCrop.default, {
    image: editedUrl || url,
    disabled: inProgress,
    minZoom: MIN_ZOOM / 100,
    maxZoom: MAX_ZOOM / 100,
    crop: position,
    zoom: zoom / 100,
    aspect: aspect,
    onCropChange: setPosition,
    onCropComplete: function onCropComplete(newCropPercent) {
      setCrop(newCropPercent);
    },
    onZoomChange: function onZoomChange(newZoom) {
      setZoom(newZoom * 100);
    }
  }), inProgress && (0, _element.createElement)(_components.Spinner, null)), (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.Dropdown, {
    contentClassName: "wp-block-image__zoom",
    popoverProps: POPOVER_PROPS,
    renderToggle: function renderToggle(_ref6) {
      var isOpen = _ref6.isOpen,
          onToggle = _ref6.onToggle;
      return (0, _element.createElement)(_components.ToolbarButton, {
        icon: _icons.search,
        label: (0, _i18n.__)('Zoom'),
        onClick: onToggle,
        "aria-expanded": isOpen,
        disabled: inProgress
      });
    },
    renderContent: function renderContent() {
      return (0, _element.createElement)(_components.RangeControl, {
        min: MIN_ZOOM,
        max: MAX_ZOOM,
        value: Math.round(zoom),
        onChange: setZoom
      });
    }
  }), (0, _element.createElement)(_components.ToolbarItem, null, function (toggleProps) {
    return (0, _element.createElement)(AspectMenu, {
      toggleProps: toggleProps,
      isDisabled: inProgress,
      onClick: setAspect,
      value: aspect,
      defaultValue: naturalWidth / naturalHeight
    });
  })), (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    icon: _icons.rotateRight,
    label: (0, _i18n.__)('Rotate'),
    onClick: rotate,
    disabled: inProgress
  })), (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    onClick: apply,
    disabled: inProgress
  }, (0, _i18n.__)('Apply')), (0, _element.createElement)(_components.ToolbarButton, {
    onClick: function onClick() {
      return setIsEditingImage(false);
    }
  }, (0, _i18n.__)('Cancel')))));
}
//# sourceMappingURL=image-editor.js.map