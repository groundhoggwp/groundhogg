"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.BlockList = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _lodash = require("lodash");

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _style = _interopRequireDefault(require("./style.scss"));

var _blockListAppender = _interopRequireDefault(require("../block-list-appender"));

var _blockListItem = _interopRequireDefault(require("./block-list-item.native"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var BlockListContext = (0, _element.createContext)();
var stylesMemo = {};

var getStyles = function getStyles(isRootList, isStackedHorizontally, horizontalAlignment) {
  if (isRootList) {
    return;
  }

  var styleName = "".concat(isStackedHorizontally, "-").concat(horizontalAlignment);

  if (stylesMemo[styleName]) {
    return stylesMemo[styleName];
  }

  var computedStyles = [isStackedHorizontally && _style.default.horizontal, horizontalAlignment && _style.default["is-aligned-".concat(horizontalAlignment)]];
  stylesMemo[styleName] = computedStyles;
  return computedStyles;
};

var BlockList = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(BlockList, _Component);

  var _super = _createSuper(BlockList);

  function BlockList() {
    var _this;

    (0, _classCallCheck2.default)(this, BlockList);
    _this = _super.apply(this, arguments);
    _this.extraData = {
      parentWidth: _this.props.parentWidth,
      renderFooterAppender: _this.props.renderFooterAppender,
      renderAppender: _this.props.renderAppender,
      onDeleteBlock: _this.props.onDeleteBlock,
      contentStyle: _this.props.contentstyle
    };
    _this.renderItem = _this.renderItem.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderBlockListFooter = _this.renderBlockListFooter.bind((0, _assertThisInitialized2.default)(_this));
    _this.onCaretVerticalPositionChange = _this.onCaretVerticalPositionChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.scrollViewInnerRef = _this.scrollViewInnerRef.bind((0, _assertThisInitialized2.default)(_this));
    _this.addBlockToEndOfPost = _this.addBlockToEndOfPost.bind((0, _assertThisInitialized2.default)(_this));
    _this.shouldFlatListPreventAutomaticScroll = _this.shouldFlatListPreventAutomaticScroll.bind((0, _assertThisInitialized2.default)(_this));
    _this.shouldShowInnerBlockAppender = _this.shouldShowInnerBlockAppender.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderEmptyList = _this.renderEmptyList.bind((0, _assertThisInitialized2.default)(_this));
    _this.getExtraData = _this.getExtraData.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(BlockList, [{
    key: "addBlockToEndOfPost",
    value: function addBlockToEndOfPost(newBlock) {
      this.props.insertBlock(newBlock, this.props.blockCount);
    }
  }, {
    key: "onCaretVerticalPositionChange",
    value: function onCaretVerticalPositionChange(targetId, caretY, previousCaretY) {
      _components.KeyboardAwareFlatList.handleCaretVerticalPositionChange(this.scrollViewRef, targetId, caretY, previousCaretY);
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
      return (0, _element.createElement)(EmptyListComponentCompose, {
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

      return isRootList ? (0, _element.createElement)(BlockListContext.Provider, {
        value: this.scrollViewRef
      }, this.renderList()) : (0, _element.createElement)(BlockListContext.Consumer, null, function (ref) {
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
          marginVertical = _this$props3$marginVe === void 0 ? _style.default.defaultBlock.marginTop : _this$props3$marginVe,
          _this$props3$marginHo = _this$props3.marginHorizontal,
          marginHorizontal = _this$props3$marginHo === void 0 ? _style.default.defaultBlock.marginLeft : _this$props3$marginHo,
          isFloatingToolbarVisible = _this$props3.isFloatingToolbarVisible,
          isStackedHorizontally = _this$props3.isStackedHorizontally,
          horizontalAlignment = _this$props3.horizontalAlignment;
      var parentScrollRef = extraProps.parentScrollRef;
      var blockToolbar = _style.default.blockToolbar,
          blockBorder = _style.default.blockBorder,
          headerToolbar = _style.default.headerToolbar,
          floatingToolbar = _style.default.floatingToolbar;
      var containerStyle = {
        flex: isRootList ? 1 : 0,
        // We set negative margin in the parent to remove the edge spacing between parent block and child block in ineer blocks
        marginVertical: isRootList ? 0 : -marginVertical,
        marginHorizontal: isRootList ? 0 : -marginHorizontal
      };
      return (0, _element.createElement)(_reactNative.View, {
        style: containerStyle,
        onAccessibilityEscape: clearSelectedBlock
      }, (0, _element.createElement)(_components.KeyboardAwareFlatList, (0, _extends2.default)({}, _reactNative.Platform.OS === 'android' ? {
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
        }, !isRootList && _style.default.overflowVisible],
        horizontal: horizontal,
        extraData: this.getExtraData(),
        scrollEnabled: isRootList,
        contentContainerStyle: horizontal && _style.default.horizontalContentContainer,
        style: getStyles(isRootList, isStackedHorizontally, horizontalAlignment),
        data: blockClientIds,
        keyExtractor: _lodash.identity,
        renderItem: this.renderItem,
        shouldPreventAutomaticScroll: this.shouldFlatListPreventAutomaticScroll,
        title: title,
        ListHeaderComponent: header,
        ListEmptyComponent: !isReadOnly && this.renderEmptyList,
        ListFooterComponent: this.renderBlockListFooter
      })), this.shouldShowInnerBlockAppender() && (0, _element.createElement)(_reactNative.View, {
        style: {
          marginHorizontal: marginHorizontal - _style.default.innerAppender.marginLeft
        }
      }, (0, _element.createElement)(_blockListAppender.default, {
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
          marginVertical = _this$props4$marginVe === void 0 ? _style.default.defaultBlock.marginTop : _this$props4$marginVe,
          _this$props4$marginHo = _this$props4.marginHorizontal,
          marginHorizontal = _this$props4$marginHo === void 0 ? _style.default.defaultBlock.marginLeft : _this$props4$marginHo;
      return (0, _element.createElement)(_blockListItem.default, {
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

      var paragraphBlock = (0, _blocks.createBlock)('core/paragraph');
      var _this$props5 = this.props,
          isReadOnly = _this$props5.isReadOnly,
          _this$props5$withFoot = _this$props5.withFooter,
          withFooter = _this$props5$withFoot === void 0 ? true : _this$props5$withFoot,
          renderFooterAppender = _this$props5.renderFooterAppender;

      if (!isReadOnly && withFooter) {
        return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
          accessibilityLabel: (0, _i18n.__)('Add paragraph block'),
          onPress: function onPress() {
            _this4.addBlockToEndOfPost(paragraphBlock);
          }
        }, (0, _element.createElement)(_reactNative.View, {
          style: _style.default.blockListFooter
        })));
      } else if (renderFooterAppender) {
        return renderFooterAppender();
      }

      return null;
    }
  }]);
  return BlockList;
}(_element.Component);

exports.BlockList = BlockList;

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
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
}), (0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      insertBlock = _dispatch.insertBlock,
      replaceBlock = _dispatch.replaceBlock,
      clearSelectedBlock = _dispatch.clearSelectedBlock;

  return {
    clearSelectedBlock: clearSelectedBlock,
    insertBlock: insertBlock,
    replaceBlock: replaceBlock
  };
}), _compose.withPreferredColorScheme])(BlockList);

exports.default = _default;

var EmptyListComponent = /*#__PURE__*/function (_Component2) {
  (0, _inherits2.default)(EmptyListComponent, _Component2);

  var _super2 = _createSuper(EmptyListComponent);

  function EmptyListComponent() {
    (0, _classCallCheck2.default)(this, EmptyListComponent);
    return _super2.apply(this, arguments);
  }

  (0, _createClass2.default)(EmptyListComponent, [{
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

      return (0, _element.createElement)(_reactNative.View, {
        style: _style.default.defaultAppender
      }, (0, _element.createElement)(_components.ReadableContentView, {
        align: renderAppender ? _components.WIDE_ALIGNMENTS.alignments.full : undefined
      }, (0, _element.createElement)(_blockListAppender.default, {
        rootClientId: rootClientId,
        renderAppender: renderAppender,
        showSeparator: shouldShowInsertionPoint
      })));
    }
  }]);
  return EmptyListComponent;
}(_element.Component);

var EmptyListComponentCompose = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref3) {
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