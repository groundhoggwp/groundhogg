import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { Animated, Easing, Text, TouchableWithoutFeedback, View, Dimensions } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './style.scss';

var Tooltip = function Tooltip(_ref) {
  var onTooltipHidden = _ref.onTooltipHidden;

  var _useState = useState(true),
      _useState2 = _slicedToArray(_useState, 2),
      visible = _useState2[0],
      setVisible = _useState2[1];

  var animationValue = useRef(new Animated.Value(0)).current;
  useEffect(function () {
    startAnimation();
  }, [visible]);

  var onHide = function onHide() {
    setVisible(false);
  };

  var startAnimation = function startAnimation() {
    Animated.timing(animationValue, {
      toValue: visible ? 1 : 0,
      duration: visible ? 300 : 150,
      useNativeDriver: true,
      delay: visible ? 500 : 0,
      easing: Easing.out(Easing.quad)
    }).start(function () {
      if (!visible && onTooltipHidden) {
        onTooltipHidden();
      }
    });
  };

  var stylesOverlay = [styles.overlay, {
    height: Dimensions.get('window').height
  }];
  return createElement(Fragment, null, createElement(TouchableWithoutFeedback, {
    onPress: onHide
  }, createElement(View, {
    style: stylesOverlay
  })), createElement(Animated.View, {
    style: {
      opacity: animationValue,
      transform: [{
        translateY: animationValue.interpolate({
          inputRange: [0, 1],
          outputRange: [visible ? 4 : -8, -8]
        })
      }]
    }
  }, createElement(TouchableWithoutFeedback, {
    onPress: onHide
  }, createElement(View, {
    style: [styles.tooltip, {
      shadowColor: styles.tooltipShadow.color,
      shadowOffset: {
        width: 0,
        height: 2
      },
      shadowOpacity: 0.25,
      shadowRadius: 2,
      elevation: 2
    }]
  }, createElement(Text, {
    style: styles.text
  }, __('Try a starter layout')), createElement(View, {
    style: styles.arrow
  })))));
};

export default Tooltip;
//# sourceMappingURL=index.native.js.map