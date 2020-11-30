"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var MIN_SPACER_HEIGHT = 1;
var MAX_SPACER_HEIGHT = 500;

var SpacerEdit = function SpacerEdit(_ref) {
  var attributes = _ref.attributes,
      isSelected = _ref.isSelected,
      setAttributes = _ref.setAttributes,
      onResizeStart = _ref.onResizeStart,
      onResizeStop = _ref.onResizeStop;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isResizing = _useState2[0],
      setIsResizing = _useState2[1];

  var height = attributes.height;

  var updateHeight = function updateHeight(value) {
    setAttributes({
      height: value
    });
  };

  var handleOnResizeStart = function handleOnResizeStart() {
    onResizeStart.apply(void 0, arguments);
    setIsResizing(true);
  };

  var handleOnResizeStop = function handleOnResizeStop(event, direction, elt, delta) {
    onResizeStop();
    var spacerHeight = Math.min(parseInt(height + delta.height, 10), MAX_SPACER_HEIGHT);
    updateHeight(spacerHeight);
    setIsResizing(false);
  };

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ResizableBox, {
    className: (0, _classnames.default)('block-library-spacer__resize-container', {
      'is-selected': isSelected
    }),
    size: {
      height: height
    },
    minHeight: MIN_SPACER_HEIGHT,
    enable: {
      top: false,
      right: false,
      bottom: true,
      left: false,
      topRight: false,
      bottomRight: false,
      bottomLeft: false,
      topLeft: false
    },
    onResizeStart: handleOnResizeStart,
    onResizeStop: handleOnResizeStop,
    showHandle: isSelected,
    __experimentalShowTooltip: true,
    __experimentalTooltipProps: {
      axis: 'y',
      position: 'bottom',
      isVisible: isResizing
    }
  }), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Spacer settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Height in pixels'),
    min: MIN_SPACER_HEIGHT,
    max: Math.max(MAX_SPACER_HEIGHT, height),
    value: height,
    onChange: updateHeight
  }))));
};

var _default = (0, _compose.compose)([(0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      toggleSelection = _dispatch.toggleSelection;

  return {
    onResizeStart: function onResizeStart() {
      return toggleSelection(false);
    },
    onResizeStop: function onResizeStop() {
      return toggleSelection(true);
    }
  };
}), _compose.withInstanceId])(SpacerEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map