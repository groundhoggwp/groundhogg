"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _objectDestructuringEmpty2 = _interopRequireDefault(require("@babel/runtime/helpers/objectDestructuringEmpty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var hitSlop = {
  top: 22,
  bottom: 22,
  left: 22,
  right: 22
};

function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return (0, _lodash.map)(innerBlocksTemplate, function (_ref) {
    var _ref2 = (0, _slicedToArray2.default)(_ref, 3),
        name = _ref2[0],
        attributes = _ref2[1],
        _ref2$ = _ref2[2],
        innerBlocks = _ref2$ === void 0 ? [] : _ref2$;

    return (0, _blocks.createBlock)(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
  });
}

function BlockVariationPicker(_ref3) {
  var isVisible = _ref3.isVisible,
      onClose = _ref3.onClose,
      clientId = _ref3.clientId,
      variations = _ref3.variations;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      replaceInnerBlocks = _useDispatch.replaceInnerBlocks;

  var isIOS = _reactNative.Platform.OS === 'ios';
  var cancelButtonStyle = (0, _compose.usePreferredColorSchemeStyle)(_style.default.cancelButton, _style.default.cancelButtonDark);
  var leftButton = (0, _element.useMemo)(function () {
    return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
      onPress: onClose,
      hitSlop: hitSlop
    }, (0, _element.createElement)(_reactNative.View, null, isIOS ? (0, _element.createElement)(_reactNative.Text, {
      style: cancelButtonStyle,
      maxFontSizeMultiplier: 2
    }, (0, _i18n.__)('Cancel')) : (0, _element.createElement)(_icons.Icon, {
      icon: _icons.close,
      size: 24,
      style: _style.default.closeIcon
    })));
  }, [onClose, cancelButtonStyle]);

  var onVariationSelect = function onVariationSelect(variation) {
    replaceInnerBlocks(clientId, createBlocksFromInnerBlocksTemplate(variation.innerBlocks), false);
    onClose();
  };

  return (0, _element.createElement)(_components.BottomSheet, {
    isVisible: isVisible,
    onClose: onClose,
    title: (0, _i18n.__)('Select a layout'),
    contentStyle: _style.default.contentStyle,
    leftButton: leftButton
  }, (0, _element.createElement)(_reactNative.ScrollView, {
    horizontal: true,
    showsHorizontalScrollIndicator: false,
    contentContainerStyle: _style.default.contentContainerStyle,
    style: _style.default.containerStyle
  }, variations.map(function (v) {
    return (0, _element.createElement)(_components.InserterButton, {
      item: v,
      key: v.name,
      onSelect: function onSelect() {
        return onVariationSelect(v);
      }
    });
  })), (0, _element.createElement)(_components.PanelBody, null, (0, _element.createElement)(_components.FooterMessageControl, {
    label: (0, _i18n.__)('Note: Column layout may vary between themes and screen sizes')
  })));
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref4) {
  (0, _objectDestructuringEmpty2.default)(_ref4);

  var _select = select('core/blocks'),
      getBlockVariations = _select.getBlockVariations;

  return {
    date: getBlockVariations('core/columns', 'block')
  };
}))(BlockVariationPicker);

exports.default = _default;
//# sourceMappingURL=index.native.js.map