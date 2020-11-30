import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Animated, Easing, View, Platform } from 'react-native';
/**
 * WordPress dependencies
 */

import { ToolbarButton, Toolbar } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
import NavigateUpSVG from './nav-up-icon';
import BlockSelectionButton from '../block-list/block-selection-button.native';
var EASE_IN_DURATION = 250;
var EASE_OUT_DURATION = 80;
var TRANSLATION_RANGE = 8;
var opacity = new Animated.Value(0);

var FloatingToolbar = function FloatingToolbar(_ref) {
  var selectedClientId = _ref.selectedClientId,
      parentId = _ref.parentId,
      showFloatingToolbar = _ref.showFloatingToolbar,
      onNavigateUp = _ref.onNavigateUp,
      isRTL = _ref.isRTL;

  // Sustain old selection for proper block selection button rendering when exit animation is ongoing.
  var _useState = useState({}),
      _useState2 = _slicedToArray(_useState, 2),
      previousSelection = _useState2[0],
      setPreviousSelection = _useState2[1];

  useEffect(function () {
    Animated.timing(opacity, {
      toValue: showFloatingToolbar ? 1 : 0,
      duration: showFloatingToolbar ? EASE_IN_DURATION : EASE_OUT_DURATION,
      easing: Easing.ease,
      useNativeDriver: true
    }).start();
  }, [showFloatingToolbar]);
  useEffect(function () {
    if (showFloatingToolbar) setPreviousSelection({
      clientId: selectedClientId,
      parentId: parentId
    });
  }, [selectedClientId]);
  var translationRange = (Platform.OS === 'android' ? -1 : 1) * TRANSLATION_RANGE;
  var translation = opacity.interpolate({
    inputRange: [0, 1],
    outputRange: [translationRange, 0]
  });
  var animationStyle = {
    opacity: opacity,
    transform: [{
      translateY: translation
    }]
  };
  var previousSelectedClientId = previousSelection.clientId,
      previousSelectedParentId = previousSelection.parentId;
  var showPrevious = previousSelectedClientId && !showFloatingToolbar;
  var blockSelectionButtonClientId = showPrevious ? previousSelectedClientId : selectedClientId;
  var showNavUpButton = !!parentId || showPrevious && !!previousSelectedParentId;
  return !!opacity && createElement(Animated.View, {
    style: [styles.floatingToolbar, animationStyle]
  }, showNavUpButton && createElement(Toolbar, {
    passedStyle: styles.toolbar
  }, createElement(ToolbarButton, {
    title: __('Navigate Up'),
    onClick: !showPrevious && function () {
      return onNavigateUp(parentId);
    },
    icon: createElement(NavigateUpSVG, {
      isRTL: isRTL
    })
  }), createElement(View, {
    style: styles.pipe
  })), createElement(BlockSelectionButton, {
    clientId: blockSelectionButtonClientId
  }));
};

export default compose([withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlockHierarchyRootClientId = _select.getBlockHierarchyRootClientId,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockCount = _select.getBlockCount,
      getSettings = _select.getSettings;

  var selectedClientId = getSelectedBlockClientId();
  if (!selectedClientId) return;
  var rootBlockId = getBlockHierarchyRootClientId(selectedClientId);
  return {
    selectedClientId: selectedClientId,
    showFloatingToolbar: !!getBlockCount(rootBlockId),
    parentId: getBlockRootClientId(selectedClientId),
    isRTL: getSettings().isRTL
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      selectBlock = _dispatch.selectBlock;

  return {
    onNavigateUp: function onNavigateUp(clientId, initialPosition) {
      selectBlock(clientId, initialPosition);
    }
  };
})])(FloatingToolbar);
//# sourceMappingURL=index.native.js.map