"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.KeyboardAwareFlatList = void 0;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _react = _interopRequireDefault(require("react"));

var _reactNativeKeyboardAwareScrollView = require("react-native-keyboard-aware-scroll-view");

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _this = void 0;

/**
 * WordPress dependencies
 */
var List = _react.default.memo(_reactNative.FlatList, _lodash.isEqual);

var KeyboardAwareFlatList = function KeyboardAwareFlatList(_ref) {
  var extraScrollHeight = _ref.extraScrollHeight,
      shouldPreventAutomaticScroll = _ref.shouldPreventAutomaticScroll,
      _innerRef = _ref.innerRef,
      autoScroll = _ref.autoScroll,
      scrollViewStyle = _ref.scrollViewStyle,
      inputAccessoryViewHeight = _ref.inputAccessoryViewHeight,
      listProps = (0, _objectWithoutProperties2.default)(_ref, ["extraScrollHeight", "shouldPreventAutomaticScroll", "innerRef", "autoScroll", "scrollViewStyle", "inputAccessoryViewHeight"]);
  return (0, _element.createElement)(_reactNativeKeyboardAwareScrollView.KeyboardAwareScrollView, {
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
  }, (0, _element.createElement)(List, listProps));
};

exports.KeyboardAwareFlatList = KeyboardAwareFlatList;

KeyboardAwareFlatList.handleCaretVerticalPositionChange = function (scrollView, targetId, caretY, previousCaretY) {
  if (previousCaretY) {
    //if this is not the first tap
    scrollView.props.refreshScrollForField(targetId);
  }
};

var _default = KeyboardAwareFlatList;
exports.default = _default;
//# sourceMappingURL=index.ios.js.map