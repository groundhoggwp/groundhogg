import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, TouchableWithoutFeedback, Text, Dimensions, Animated, Easing, Image } from 'react-native';
/**
 * WordPress dependencies
 */

import { BottomSheet } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';
var MAX_ITEM_WIDTH = 120;
var HALF_COLUMN = 0.5;

function StylePreview(_ref) {
  var _onPress = _ref.onPress,
      isActive = _ref.isActive,
      style = _ref.style,
      url = _ref.url;

  var _useState = useState(MAX_ITEM_WIDTH),
      _useState2 = _slicedToArray(_useState, 2),
      itemWidth = _useState2[0],
      setItemWidth = _useState2[1];

  var label = style.label,
      name = style.name;
  var opacity = useRef(new Animated.Value(1)).current;

  function onLayout() {
    var columnsNum = // To indicate scroll availabilty, there is a need to display additional half the column
    Math.floor(BottomSheet.getWidth() / MAX_ITEM_WIDTH) + HALF_COLUMN;
    setItemWidth(BottomSheet.getWidth() / columnsNum);
  }

  useEffect(function () {
    onLayout();
    Dimensions.addEventListener('change', onLayout);
    return function () {
      Dimensions.removeEventListener('change', onLayout);
    };
  }, []);
  var labelStyle = usePreferredColorSchemeStyle(styles.label, styles.labelDark);

  var animateOutline = function animateOutline() {
    opacity.setValue(0);
    Animated.timing(opacity, {
      toValue: 1,
      duration: 100,
      useNativeDriver: true,
      easing: Easing.linear
    }).start();
  };

  var innerOutlineStyle = usePreferredColorSchemeStyle(styles.innerOutline, styles.innerOutlineDark);

  var getOutline = function getOutline(outlineStyles) {
    return outlineStyles.map(function (outlineStyle) {
      return createElement(Animated.View, {
        style: [outlineStyle, {
          opacity: opacity
        }, styles[name]],
        key: outlineStyle
      });
    });
  };

  return createElement(TouchableWithoutFeedback, {
    onPress: function onPress() {
      _onPress();

      animateOutline();
    }
  }, createElement(View, {
    style: [styles.container, {
      width: itemWidth
    }]
  }, createElement(View, {
    style: styles.imageWrapper
  }, isActive && getOutline([styles.outline, innerOutlineStyle]), createElement(Image, {
    style: [styles.image, styles[name]],
    source: {
      uri: url
    }
  })), createElement(Text, {
    style: [labelStyle, isActive && styles.labelSelected]
  }, label)));
}

export default StylePreview;
//# sourceMappingURL=preview.native.js.map