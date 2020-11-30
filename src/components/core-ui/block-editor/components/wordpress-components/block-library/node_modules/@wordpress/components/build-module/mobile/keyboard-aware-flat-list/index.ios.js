import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";

var _this = this;

import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import React from 'react';
import { KeyboardAwareScrollView } from 'react-native-keyboard-aware-scroll-view';
import { FlatList } from 'react-native';
import { isEqual } from 'lodash';
/**
 * WordPress dependencies
 */

var List = React.memo(FlatList, isEqual);
export var KeyboardAwareFlatList = function KeyboardAwareFlatList(_ref) {
  var extraScrollHeight = _ref.extraScrollHeight,
      shouldPreventAutomaticScroll = _ref.shouldPreventAutomaticScroll,
      _innerRef = _ref.innerRef,
      autoScroll = _ref.autoScroll,
      scrollViewStyle = _ref.scrollViewStyle,
      inputAccessoryViewHeight = _ref.inputAccessoryViewHeight,
      listProps = _objectWithoutProperties(_ref, ["extraScrollHeight", "shouldPreventAutomaticScroll", "innerRef", "autoScroll", "scrollViewStyle", "inputAccessoryViewHeight"]);

  return createElement(KeyboardAwareScrollView, {
    style: [{
      flex: 1
    }, scrollViewStyle],
    keyboardDismissMode: "none",
    enableResetScrollToCoords: false,
    keyboardShouldPersistTaps: "handled",
    extraScrollHeight: extraScrollHeight,
    extraHeight: 0,
    inputAccessoryViewHeight: inputAccessoryViewHeight,
    enableAutomaticScroll: autoScroll === undefined ? false : autoScroll,
    innerRef: function innerRef(ref) {
      _this.scrollViewRef = ref;

      _innerRef(ref);
    },
    onKeyboardWillHide: function onKeyboardWillHide() {
      _this.keyboardWillShowIndicator = false;
    },
    onKeyboardDidHide: function onKeyboardDidHide() {
      setTimeout(function () {
        if (!_this.keyboardWillShowIndicator && _this.latestContentOffsetY !== undefined && !shouldPreventAutomaticScroll()) {
          // Reset the content position if keyboard is still closed
          if (_this.scrollViewRef) {
            _this.scrollViewRef.props.scrollToPosition(0, _this.latestContentOffsetY, true);
          }
        }
      }, 50);
    },
    onKeyboardWillShow: function onKeyboardWillShow() {
      _this.keyboardWillShowIndicator = true;
    },
    scrollEnabled: listProps.scrollEnabled,
    onScroll: function onScroll(event) {
      _this.latestContentOffsetY = event.nativeEvent.contentOffset.y;
    }
  }, createElement(List, listProps));
};

KeyboardAwareFlatList.handleCaretVerticalPositionChange = function (scrollView, targetId, caretY, previousCaretY) {
  if (previousCaretY) {
    //if this is not the first tap
    scrollView.props.refreshScrollForField(targetId);
  }
};

export default KeyboardAwareFlatList;
//# sourceMappingURL=index.ios.js.map