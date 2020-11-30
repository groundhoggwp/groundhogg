import _objectDestructuringEmpty from "@babel/runtime/helpers/esm/objectDestructuringEmpty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { ScrollView, View, Text, TouchableWithoutFeedback, Platform } from 'react-native';
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { withSelect, useDispatch } from '@wordpress/data';
import { compose, usePreferredColorSchemeStyle } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { PanelBody, BottomSheet, FooterMessageControl, InserterButton } from '@wordpress/components';
import { Icon, close } from '@wordpress/icons';
import { useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './style.scss';
var hitSlop = {
  top: 22,
  bottom: 22,
  left: 22,
  right: 22
};

function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return map(innerBlocksTemplate, function (_ref) {
    var _ref2 = _slicedToArray(_ref, 3),
        name = _ref2[0],
        attributes = _ref2[1],
        _ref2$ = _ref2[2],
        innerBlocks = _ref2$ === void 0 ? [] : _ref2$;

    return createBlock(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
  });
}

function BlockVariationPicker(_ref3) {
  var isVisible = _ref3.isVisible,
      onClose = _ref3.onClose,
      clientId = _ref3.clientId,
      variations = _ref3.variations;

  var _useDispatch = useDispatch('core/block-editor'),
      replaceInnerBlocks = _useDispatch.replaceInnerBlocks;

  var isIOS = Platform.OS === 'ios';
  var cancelButtonStyle = usePreferredColorSchemeStyle(styles.cancelButton, styles.cancelButtonDark);
  var leftButton = useMemo(function () {
    return createElement(TouchableWithoutFeedback, {
      onPress: onClose,
      hitSlop: hitSlop
    }, createElement(View, null, isIOS ? createElement(Text, {
      style: cancelButtonStyle,
      maxFontSizeMultiplier: 2
    }, __('Cancel')) : createElement(Icon, {
      icon: close,
      size: 24,
      style: styles.closeIcon
    })));
  }, [onClose, cancelButtonStyle]);

  var onVariationSelect = function onVariationSelect(variation) {
    replaceInnerBlocks(clientId, createBlocksFromInnerBlocksTemplate(variation.innerBlocks), false);
    onClose();
  };

  return createElement(BottomSheet, {
    isVisible: isVisible,
    onClose: onClose,
    title: __('Select a layout'),
    contentStyle: styles.contentStyle,
    leftButton: leftButton
  }, createElement(ScrollView, {
    horizontal: true,
    showsHorizontalScrollIndicator: false,
    contentContainerStyle: styles.contentContainerStyle,
    style: styles.containerStyle
  }, variations.map(function (v) {
    return createElement(InserterButton, {
      item: v,
      key: v.name,
      onSelect: function onSelect() {
        return onVariationSelect(v);
      }
    });
  })), createElement(PanelBody, null, createElement(FooterMessageControl, {
    label: __('Note: Column layout may vary between themes and screen sizes')
  })));
}

export default compose(withSelect(function (select, _ref4) {
  _objectDestructuringEmpty(_ref4);

  var _select = select('core/blocks'),
      getBlockVariations = _select.getBlockVariations;

  return {
    date: getBlockVariations('core/columns', 'block')
  };
}))(BlockVariationPicker);
//# sourceMappingURL=index.native.js.map