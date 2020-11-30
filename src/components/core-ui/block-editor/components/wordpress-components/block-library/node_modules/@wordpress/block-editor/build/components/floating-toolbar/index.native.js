"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _styles = _interopRequireDefault(require("./styles.scss"));

var _navUpIcon = _interopRequireDefault(require("./nav-up-icon"));

var _blockSelectionButton = _interopRequireDefault(require("../block-list/block-selection-button.native"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var EASE_IN_DURATION = 250;
var EASE_OUT_DURATION = 80;
var TRANSLATION_RANGE = 8;
var opacity = new _reactNative.Animated.Value(0);

var FloatingToolbar = function FloatingToolbar(_ref) {
  var selectedClientId = _ref.selectedClientId,
      parentId = _ref.parentId,
      showFloatingToolbar = _ref.showFloatingToolbar,
      onNavigateUp = _ref.onNavigateUp,
      isRTL = _ref.isRTL;

  // Sustain old selection for proper block selection button rendering when exit animation is ongoing.
  var _useState = (0, _element.useState)({}),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      previousSelection = _useState2[0],
      setPreviousSelection = _useState2[1];

  (0, _element.useEffect)(function () {
    _reactNative.Animated.timing(opacity, {
      toValue: showFloatingToolbar ? 1 : 0,
      duration: showFloatingToolbar ? EASE_IN_DURATION : EASE_OUT_DURATION,
      easing: _reactNative.Easing.ease,
      useNativeDriver: true
    }).start();
  }, [showFloatingToolbar]);
  (0, _element.useEffect)(function () {
    if (showFloatingToolbar) setPreviousSelection({
      clientId: selectedClientId,
      parentId: parentId
    });
  }, [selectedClientId]);
  var translationRange = (_reactNative.Platform.OS === 'android' ? -1 : 1) * TRANSLATION_RANGE;
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
  return !!opacity && (0, _element.createElement)(_reactNative.Animated.View, {
    style: [_styles.default.floatingToolbar, animationStyle]
  }, showNavUpButton && (0, _element.createElement)(_components.Toolbar, {
    passedStyle: _styles.default.toolbar
  }, (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Navigate Up'),
    onClick: !showPrevious && function () {
      return onNavigateUp(parentId);
    },
    icon: (0, _element.createElement)(_navUpIcon.default, {
      isRTL: isRTL
    })
  }), (0, _element.createElement)(_reactNative.View, {
    style: _styles.default.pipe
  })), (0, _element.createElement)(_blockSelectionButton.default, {
    clientId: blockSelectionButtonClientId
  }));
};

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select) {
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
}), (0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      selectBlock = _dispatch.selectBlock;

  return {
    onNavigateUp: function onNavigateUp(clientId, initialPosition) {
      selectBlock(clientId, initialPosition);
    }
  };
})])(FloatingToolbar);

exports.default = _default;
//# sourceMappingURL=index.native.js.map