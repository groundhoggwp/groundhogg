import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { FlatList, View, TouchableHighlight, TouchableWithoutFeedback, Dimensions } from 'react-native';
import { pick } from 'lodash';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { createBlock, rawHandler } from '@wordpress/blocks';
import { withDispatch, withSelect } from '@wordpress/data';
import { withInstanceId, compose } from '@wordpress/compose';
import { BottomSheet, BottomSheetConsumer, InserterButton } from '@wordpress/components';
/**
 * Internal dependencies
 */

import styles from './style.scss';
var MIN_COL_NUM = 3;
export var InserterMenu = /*#__PURE__*/function (_Component) {
  _inherits(InserterMenu, _Component);

  var _super = _createSuper(InserterMenu);

  function InserterMenu() {
    var _this;

    _classCallCheck(this, InserterMenu);

    _this = _super.apply(this, arguments);
    _this.onClose = _this.onClose.bind(_assertThisInitialized(_this));
    _this.onLayout = _this.onLayout.bind(_assertThisInitialized(_this));
    _this.renderItem = _this.renderItem.bind(_assertThisInitialized(_this));
    _this.state = {
      numberOfColumns: MIN_COL_NUM
    };
    Dimensions.addEventListener('change', _this.onLayout);
    return _this;
  }

  _createClass(InserterMenu, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.props.showInsertionPoint();
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.props.hideInsertionPoint();
      Dimensions.removeEventListener('change', this.onLayout);
    }
  }, {
    key: "calculateMinItemWidth",
    value: function calculateMinItemWidth(bottomSheetWidth) {
      var _styles$columnPadding = styles.columnPadding,
          paddingLeft = _styles$columnPadding.paddingLeft,
          paddingRight = _styles$columnPadding.paddingRight;
      return (bottomSheetWidth - 2 * (paddingLeft + paddingRight)) / MIN_COL_NUM;
    }
  }, {
    key: "calculateItemWidth",
    value: function calculateItemWidth() {
      var _InserterButton$Style = InserterButton.Styles.modalItem,
          itemPaddingLeft = _InserterButton$Style.paddingLeft,
          itemPaddingRight = _InserterButton$Style.paddingRight;
      var itemWidth = InserterButton.Styles.modalIconWrapper.width;
      return itemWidth + itemPaddingLeft + itemPaddingRight;
    }
  }, {
    key: "calculateColumnsProperties",
    value: function calculateColumnsProperties() {
      var bottomSheetWidth = BottomSheet.getWidth();
      var _styles$columnPadding2 = styles.columnPadding,
          paddingLeft = _styles$columnPadding2.paddingLeft,
          paddingRight = _styles$columnPadding2.paddingRight;
      var itemTotalWidth = this.calculateItemWidth();
      var containerTotalWidth = bottomSheetWidth - (paddingLeft + paddingRight);
      var numofColumns = Math.floor(containerTotalWidth / itemTotalWidth);

      if (numofColumns < MIN_COL_NUM) {
        return {
          numOfColumns: MIN_COL_NUM,
          itemWidth: this.calculateMinItemWidth(bottomSheetWidth),
          maxWidth: containerTotalWidth / MIN_COL_NUM
        };
      }

      return {
        numOfColumns: numofColumns,
        maxWidth: containerTotalWidth / numofColumns
      };
    }
  }, {
    key: "onClose",
    value: function onClose() {
      // if should replace but didn't insert any block
      // re-insert default block
      if (this.props.shouldReplaceBlock) {
        this.props.insertDefaultBlock();
      }

      this.props.onDismiss();
    }
  }, {
    key: "onLayout",
    value: function onLayout() {
      var _this$calculateColumn = this.calculateColumnsProperties(),
          numOfColumns = _this$calculateColumn.numOfColumns,
          itemWidth = _this$calculateColumn.itemWidth,
          maxWidth = _this$calculateColumn.maxWidth;

      var numberOfColumns = numOfColumns;
      this.setState({
        numberOfColumns: numberOfColumns,
        itemWidth: itemWidth,
        maxWidth: maxWidth
      });
    }
  }, {
    key: "renderItem",
    value: function renderItem(_ref) {
      var item = _ref.item;
      var _this$state = this.state,
          itemWidth = _this$state.itemWidth,
          maxWidth = _this$state.maxWidth;
      var onSelect = this.props.onSelect;
      return createElement(InserterButton, {
        item: item,
        itemWidth: itemWidth,
        maxWidth: maxWidth,
        onSelect: onSelect
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var items = this.props.items;
      var numberOfColumns = this.state.numberOfColumns;
      return createElement(BottomSheet, {
        isVisible: true,
        onClose: this.onClose,
        hideHeader: true,
        isChildrenScrollable: true
      }, createElement(TouchableHighlight, {
        accessible: false
      }, createElement(BottomSheetConsumer, null, function (_ref2) {
        var listProps = _ref2.listProps;
        return createElement(FlatList, _extends({
          onLayout: _this2.onLayout,
          key: "InserterUI-".concat(numberOfColumns) //re-render when numberOfColumns changes
          ,
          keyboardShouldPersistTaps: "always",
          numColumns: numberOfColumns,
          data: items,
          ItemSeparatorComponent: function ItemSeparatorComponent() {
            return createElement(TouchableWithoutFeedback, {
              accessible: false
            }, createElement(View, {
              style: styles.rowSeparator
            }));
          },
          keyExtractor: function keyExtractor(item) {
            return item.name;
          },
          renderItem: _this2.renderItem
        }, listProps));
      })));
    }
  }]);

  return InserterMenu;
}(Component);
export default compose(withSelect(function (select, _ref3) {
  var clientId = _ref3.clientId,
      isAppender = _ref3.isAppender,
      rootClientId = _ref3.rootClientId;

  var _select = select('core/block-editor'),
      getInserterItems = _select.getInserterItems,
      getBlockName = _select.getBlockName,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockSelectionEnd = _select.getBlockSelectionEnd,
      getSettings = _select.getSettings,
      canInsertBlockType = _select.canInsertBlockType;

  var _select2 = select('core/blocks'),
      getChildBlockNames = _select2.getChildBlockNames,
      getBlockType = _select2.getBlockType;

  var _select3 = select('core/editor'),
      getClipboard = _select3.getClipboard;

  var destinationRootClientId = rootClientId;

  if (!destinationRootClientId && !clientId && !isAppender) {
    var end = getBlockSelectionEnd();

    if (end) {
      destinationRootClientId = getBlockRootClientId(end) || undefined;
    }
  }

  var destinationRootBlockName = getBlockName(destinationRootClientId);

  var _getSettings = getSettings(),
      shouldInsertAtTheTop = _getSettings.__experimentalShouldInsertAtTheTop;

  var clipboard = getClipboard();
  var clipboardBlock = clipboard && rawHandler({
    HTML: clipboard
  })[0];
  var shouldAddClipboardBlock = clipboardBlock && canInsertBlockType(clipboardBlock.name, destinationRootClientId);
  return {
    rootChildBlocks: getChildBlockNames(destinationRootBlockName),
    items: shouldAddClipboardBlock ? [_objectSpread(_objectSpread({}, pick(getBlockType(clipboardBlock.name), ['name', 'icon'])), {}, {
      id: 'clipboard',
      initialAttributes: clipboardBlock.attributes,
      innerBlocks: clipboardBlock.innerBlocks
    })].concat(_toConsumableArray(getInserterItems(destinationRootClientId))) : getInserterItems(destinationRootClientId),
    destinationRootClientId: destinationRootClientId,
    shouldInsertAtTheTop: shouldInsertAtTheTop
  };
}), withDispatch(function (dispatch, ownProps, _ref4) {
  var select = _ref4.select;

  var _dispatch = dispatch('core/block-editor'),
      _showInsertionPoint = _dispatch.showInsertionPoint,
      hideInsertionPoint = _dispatch.hideInsertionPoint,
      removeBlock = _dispatch.removeBlock,
      resetBlocks = _dispatch.resetBlocks,
      clearSelectedBlock = _dispatch.clearSelectedBlock,
      insertBlock = _dispatch.insertBlock,
      _insertDefaultBlock = _dispatch.insertDefaultBlock;

  return {
    showInsertionPoint: function showInsertionPoint() {
      if (ownProps.shouldReplaceBlock) {
        var _select4 = select('core/block-editor'),
            getBlockOrder = _select4.getBlockOrder,
            getBlockCount = _select4.getBlockCount;

        var count = getBlockCount(); // Check if there is a rootClientId because that means it is a nested replacable block and we don't want to clear/reset all blocks.

        if (count === 1 && !ownProps.rootClientId) {
          // removing the last block is not possible with `removeBlock` action
          // it always inserts a default block if the last of the blocks have been removed
          clearSelectedBlock();
          resetBlocks([]);
        } else {
          var blockToReplace = getBlockOrder(ownProps.destinationRootClientId)[ownProps.insertionIndex];
          removeBlock(blockToReplace, false);
        }
      }

      _showInsertionPoint(ownProps.destinationRootClientId, ownProps.insertionIndex);
    },
    hideInsertionPoint: hideInsertionPoint,
    onSelect: function onSelect(item) {
      var name = item.name,
          initialAttributes = item.initialAttributes,
          innerBlocks = item.innerBlocks;
      var insertedBlock = createBlock(name, initialAttributes, innerBlocks);
      insertBlock(insertedBlock, ownProps.insertionIndex, ownProps.destinationRootClientId);
      ownProps.onSelect();
    },
    insertDefaultBlock: function insertDefaultBlock() {
      _insertDefaultBlock({}, ownProps.destinationRootClientId, ownProps.insertionIndex);
    }
  };
}), withInstanceId)(InserterMenu);
//# sourceMappingURL=menu.native.js.map