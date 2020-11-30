import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { identity } from 'lodash';
import { View, Platform, TouchableWithoutFeedback } from 'react-native';
/**
 * WordPress dependencies
 */

import { Component, createContext } from '@wordpress/element';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { KeyboardAwareFlatList, ReadableContentView, WIDE_ALIGNMENTS } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import BlockListAppender from '../block-list-appender';
import BlockListItem from './block-list-item.native';
var BlockListContext = createContext();
var stylesMemo = {};

var getStyles = function getStyles(isRootList, isStackedHorizontally, horizontalAlignment) {
  if (isRootList) {
    return;
  }

  var styleName = "".concat(isStackedHorizontally, "-").concat(horizontalAlignment);

  if (stylesMemo[styleName]) {
    return stylesMemo[styleName];
  }

  var computedStyles = [isStackedHorizontally && styles.horizontal, horizontalAlignment && styles["is-aligned-".concat(horizontalAlignment)]];
  stylesMemo[styleName] = computedStyles;
  return computedStyles;
};

export var BlockList = /*#__PURE__*/function (_Component) {
  _inherits(BlockList, _Component);

  var _super = _createSuper(BlockList);

  function BlockList() {
    var _this;

    _classCallCheck(this, BlockList);

    _this = _super.apply(this, arguments);
    _this.extraData = {
      parentWidth: _this.props.parentWidth,
      renderFooterAppender: _this.props.renderFooterAppender,
      renderAppender: _this.props.renderAppender,
      onDeleteBlock: _this.props.onDeleteBlock,
      contentStyle: _this.props.contentstyle
    };
    _this.renderItem = _this.renderItem.bind(_assertThisInitialized(_this));
    _this.renderBlockListFooter = _this.renderBlockListFooter.bind(_assertThisInitialized(_this));
    _this.onCaretVerticalPositionChange = _this.onCaretVerticalPositionChange.bind(_assertThisInitialized(_this));
    _this.scrollViewInnerRef = _this.scrollViewInnerRef.bind(_assertThisInitialized(_this));
    _this.addBlockToEndOfPost = _this.addBlockToEndOfPost.bind(_assertThisInitialized(_this));
    _this.shouldFlatListPreventAutomaticScroll = _this.shouldFlatListPreventAutomaticScroll.bind(_assertThisInitialized(_this));
    _this.shouldShowInnerBlockAppender = _this.shouldShowInnerBlockAppender.bind(_assertThisInitialized(_this));
    _this.renderEmptyList = _this.renderEmptyList.bind(_assertThisInitialized(_this));
    _this.getExtraData = _this.getExtraData.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(BlockList, [{
    key: "addBlockToEndOfPost",
    value: function addBlockToEndOfPost(newBlock) {
      this.props.insertBlock(newBlock, this.props.blockCount);
    }
  }, {
    key: "onCaretVerticalPositionChange",
    value: function onCaretVerticalPositionChange(targetId, caretY, previousCaretY) {
      KeyboardAwareFlatList.handleCaretVerticalPositionChange(this.scrollViewRef, targetId, caretY, previousCaretY);
    }
  }, {
    key: "scrollViewInnerRef",
    value: function scrollViewInnerRef(ref) {
      this.scrollViewRef = ref;
    }
  }, {
    key: "shouldFlatListPreventAutomaticScroll",
    value: function shouldFlatListPreventAutomaticScroll() {
      return this.props.isBlockInsertionPointVisible;
    }
  }, {
    key: "shouldShowInnerBlockAppender",
    value: function shouldShowInnerBlockAppender() {
      var _this$props = this.props,
          blockClientIds = _this$props.blockClientIds,
          renderAppender = _this$props.renderAppender;
      return renderAppender && blockClientIds.length > 0;
    }
  }, {
    key: "renderEmptyList",
    value: function renderEmptyList() {
      return createElement(EmptyListComponentCompose, {
        rootClientId: this.props.rootClientId,
        renderAppender: this.props.renderAppender,
        renderFooterAppender: this.props.renderFooterAppender
      });
    }
  }, {
    key: "getExtraData",
    value: function getExtraData() {
      var _this$props2 = this.props,
          parentWidth = _this$props2.parentWidth,
          renderFooterAppender = _this$props2.renderFooterAppender,
          onDeleteBlock = _this$props2.onDeleteBlock,
          contentStyle = _this$props2.contentStyle,
          renderAppender = _this$props2.renderAppender;

      if (this.extraData.parentWidth !== parentWidth || this.extraData.renderFooterAppender !== renderFooterAppender || this.extraData.onDeleteBlock !== onDeleteBlock || this.extraData.contentStyle !== contentStyle || this.extraData.renderAppender !== renderAppender) {
        this.extraData = {
          parentWidth: parentWidth,
          renderFooterAppender: renderFooterAppender,
          onDeleteBlock: onDeleteBlock,
          contentStyle: contentStyle,
          renderAppender: renderAppender
        };
      }

      return this.extraData;
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var isRootList = this.props.isRootList; // Use of Context to propagate the main scroll ref to its children e.g InnerBlocks

      return isRootList ? createElement(BlockListContext.Provider, {
        value: this.scrollViewRef
      }, this.renderList()) : createElement(BlockListContext.Consumer, null, function (ref) {
        return _this2.renderList({
          parentScrollRef: ref
        });
      });
    }
  }, {
    key: "renderList",
    value: function renderList() {
      var _this3 = this;

      var extraProps = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var _this$props3 = this.props,
          clearSelectedBlock = _this$props3.clearSelectedBlock,
          blockClientIds = _this$props3.blockClientIds,
          title = _this$props3.title,
          header = _this$props3.header,
          isReadOnly = _this$props3.isReadOnly,
          isRootList = _this$props3.isRootList,
          horizontal = _this$props3.horizontal,
          _this$props3$marginVe = _this$props3.marginVertical,
          marginVertical = _this$props3$marginVe === void 0 ? styles.defaultBlock.marginTop : _this$props3$marginVe,
          _this$props3$marginHo = _this$props3.marginHorizontal,
          marginHorizontal = _this$props3$marginHo === void 0 ? styles.defaultBlock.marginLeft : _this$props3$marginHo,
          isFloatingToolbarVisible = _this$props3.isFloatingToolbarVisible,
          isStackedHorizontally = _this$props3.isStackedHorizontally,
          horizontalAlignment = _this$props3.horizontalAlignment;
      var parentScrollRef = extraProps.parentScrollRef;
      var blockToolbar = styles.blockToolbar,
          blockBorder = styles.blockBorder,
          headerToolbar = styles.headerToolbar,
          floatingToolbar = styles.floatingToolbar;
      var containerStyle = {
        flex: isRootList ? 1 : 0,
        // We set negative margin in the parent to remove the edge spacing between parent block and child block in ineer blocks
        marginVertical: isRootList ? 0 : -marginVertical,
        marginHorizontal: isRootList ? 0 : -marginHorizontal
      };
      return createElement(View, {
        style: containerStyle,
        onAccessibilityEscape: clearSelectedBlock
      }, createElement(KeyboardAwareFlatList, _extends({}, Platform.OS === 'android' ? {
        removeClippedSubviews: false
      } : {}, {
        // Disable clipping on Android to fix focus losing. See https://github.com/wordpress-mobile/gutenberg-mobile/pull/741#issuecomment-472746541
        accessibilityLabel: "block-list",
        autoScroll: this.props.autoScroll,
        innerRef: function innerRef(ref) {
          _this3.scrollViewInnerRef(parentScrollRef || ref);
        },
        extraScrollHeight: blockToolbar.height + blockBorder.width,
        inputAccessoryViewHeight: headerToolbar.height + (isFloatingToolbarVisible ? floatingToolbar.height : 0),
        keyboardShouldPersistTaps: "always",
        scrollViewStyle: [{
          flex: isRootList ? 1 : 0
        }, !isRootList && styles.overflowVisible],
        horizontal: horizontal,
        extraData: this.getExtraData(),
        scrollEnabled: isRootList,
        contentContainerStyle: horizontal && styles.horizontalContentContainer,
        style: getStyles(isRootList, isStackedHorizontally, horizontalAlignment),
        data: blockClientIds,
        keyExtractor: identity,
        renderItem: this.renderItem,
        shouldPreventAutomaticScroll: this.shouldFlatListPreventAutomaticScroll,
        title: title,
        ListHeaderComponent: header,
        ListEmptyComponent: !isReadOnly && this.renderEmptyList,
        ListFooterComponent: this.renderBlockListFooter
      })), this.shouldShowInnerBlockAppender() && createElement(View, {
        style: {
          marginHorizontal: marginHorizontal - styles.innerAppender.marginLeft
        }
      }, createElement(BlockListAppender, {
        rootClientId: this.props.rootClientId,
        renderAppender: this.props.renderAppender,
        showSeparator: true
      })));
    }
  }, {
    key: "renderItem",
    value: function renderItem(_ref) {
      var clientId = _ref.item;
      var _this$props4 = this.props,
          contentResizeMode = _this$props4.contentResizeMode,
          contentStyle = _this$props4.contentStyle,
          onAddBlock = _this$props4.onAddBlock,
          onDeleteBlock = _this$props4.onDeleteBlock,
          rootClientId = _this$props4.rootClientId,
          isStackedHorizontally = _this$props4.isStackedHorizontally,
          parentWidth = _this$props4.parentWidth,
          _this$props4$marginVe = _this$props4.marginVertical,
          marginVertical = _this$props4$marginVe === void 0 ? styles.defaultBlock.marginTop : _this$props4$marginVe,
          _this$props4$marginHo = _this$props4.marginHorizontal,
          marginHorizontal = _this$props4$marginHo === void 0 ? styles.defaultBlock.marginLeft : _this$props4$marginHo;
      return createElement(BlockListItem, {
        isStackedHorizontally: isStackedHorizontally,
        rootClientId: rootClientId,
        clientId: clientId,
        parentWidth: parentWidth,
        contentResizeMode: contentResizeMode,
        contentStyle: contentStyle,
        onAddBlock: onAddBlock,
        marginVertical: marginVertical,
        marginHorizontal: marginHorizontal,
        onDeleteBlock: onDeleteBlock,
        shouldShowInnerBlockAppender: this.shouldShowInnerBlockAppender,
        onCaretVerticalPositionChange: this.onCaretVerticalPositionChange
      });
    }
  }, {
    key: "renderBlockListFooter",
    value: function renderBlockListFooter() {
      var _this4 = this;

      var paragraphBlock = createBlock('core/paragraph');
      var _this$props5 = this.props,
          isReadOnly = _this$props5.isReadOnly,
          _this$props5$withFoot = _this$props5.withFooter,
          withFooter = _this$props5$withFoot === void 0 ? true : _this$props5$withFoot,
          renderFooterAppender = _this$props5.renderFooterAppender;

      if (!isReadOnly && withFooter) {
        return createElement(Fragment, null, createElement(TouchableWithoutFeedback, {
          accessibilityLabel: __('Add paragraph block'),
          onPress: function onPress() {
            _this4.addBlockToEndOfPost(paragraphBlock);
          }
        }, createElement(View, {
          style: styles.blockListFooter
        })));
      } else if (renderFooterAppender) {
        return renderFooterAppender();
      }

      return null;
    }
  }]);

  return BlockList;
}(Component);
export default compose([withSelect(function (select, _ref2) {
  var rootClientId = _ref2.rootClientId,
      orientation = _ref2.orientation,
      filterInnerBlocks = _ref2.filterInnerBlocks;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockOrder = _select.getBlockOrder,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      isBlockInsertionPointVisible = _select.isBlockInsertionPointVisible,
      getSettings = _select.getSettings,
      getBlockHierarchyRootClientId = _select.getBlockHierarchyRootClientId;

  var isStackedHorizontally = orientation === 'horizontal';
  var selectedBlockClientId = getSelectedBlockClientId();
  var blockClientIds = getBlockOrder(rootClientId); // Display only block which fulfill the condition in passed `filterInnerBlocks` function

  if (filterInnerBlocks) {
    blockClientIds = filterInnerBlocks(blockClientIds);
  }

  var isReadOnly = getSettings().readOnly;
  var blockCount = getBlockCount(rootBlockId);
  var rootBlockId = getBlockHierarchyRootClientId(selectedBlockClientId);
  var hasRootInnerBlocks = !!blockCount;
  var isFloatingToolbarVisible = !!selectedBlockClientId && hasRootInnerBlocks;
  return {
    blockClientIds: blockClientIds,
    blockCount: blockCount,
    isBlockInsertionPointVisible: isBlockInsertionPointVisible(),
    isReadOnly: isReadOnly,
    isRootList: rootClientId === undefined,
    isFloatingToolbarVisible: isFloatingToolbarVisible,
    isStackedHorizontally: isStackedHorizontally
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      insertBlock = _dispatch.insertBlock,
      replaceBlock = _dispatch.replaceBlock,
      clearSelectedBlock = _dispatch.clearSelectedBlock;

  return {
    clearSelectedBlock: clearSelectedBlock,
    insertBlock: insertBlock,
    replaceBlock: replaceBlock
  };
}), withPreferredColorScheme])(BlockList);

var EmptyListComponent = /*#__PURE__*/function (_Component2) {
  _inherits(EmptyListComponent, _Component2);

  var _super2 = _createSuper(EmptyListComponent);

  function EmptyListComponent() {
    _classCallCheck(this, EmptyListComponent);

    return _super2.apply(this, arguments);
  }

  _createClass(EmptyListComponent, [{
    key: "render",
    value: function render() {
      var _this$props6 = this.props,
          shouldShowInsertionPoint = _this$props6.shouldShowInsertionPoint,
          rootClientId = _this$props6.rootClientId,
          renderAppender = _this$props6.renderAppender,
          renderFooterAppender = _this$props6.renderFooterAppender;

      if (renderFooterAppender) {
        return null;
      }

      return createElement(View, {
        style: styles.defaultAppender
      }, createElement(ReadableContentView, {
        align: renderAppender ? WIDE_ALIGNMENTS.alignments.full : undefined
      }, createElement(BlockListAppender, {
        rootClientId: rootClientId,
        renderAppender: renderAppender,
        showSeparator: shouldShowInsertionPoint
      })));
    }
  }]);

  return EmptyListComponent;
}(Component);

var EmptyListComponentCompose = compose([withSelect(function (select, _ref3) {
  var rootClientId = _ref3.rootClientId,
      orientation = _ref3.orientation;

  var _select2 = select('core/block-editor'),
      getBlockOrder = _select2.getBlockOrder,
      getBlockInsertionPoint = _select2.getBlockInsertionPoint,
      isBlockInsertionPointVisible = _select2.isBlockInsertionPointVisible;

  var isStackedHorizontally = orientation === 'horizontal';
  var blockClientIds = getBlockOrder(rootClientId);
  var insertionPoint = getBlockInsertionPoint();
  var blockInsertionPointIsVisible = isBlockInsertionPointVisible();
  var shouldShowInsertionPoint = !isStackedHorizontally && blockInsertionPointIsVisible && insertionPoint.rootClientId === rootClientId && ( // if list is empty, show the insertion point (via the default appender)
  blockClientIds.length === 0 || // or if the insertion point is right before the denoted block
  !blockClientIds[insertionPoint.index]);
  return {
    shouldShowInsertionPoint: shouldShowInsertionPoint
  };
})])(EmptyListComponent);
//# sourceMappingURL=index.native.js.map