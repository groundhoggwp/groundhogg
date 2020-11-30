"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.InserterMenu = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _style = _interopRequireDefault(require("./style.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MIN_COL_NUM = 3;

var InserterMenu = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(InserterMenu, _Component);

  var _super = _createSuper(InserterMenu);

  function InserterMenu() {
    var _this;

    (0, _classCallCheck2.default)(this, InserterMenu);
    _this = _super.apply(this, arguments);
    _this.onClose = _this.onClose.bind((0, _assertThisInitialized2.default)(_this));
    _this.onLayout = _this.onLayout.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderItem = _this.renderItem.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      numberOfColumns: MIN_COL_NUM
    };

    _reactNative.Dimensions.addEventListener('change', _this.onLayout);

    return _this;
  }

  (0, _createClass2.default)(InserterMenu, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.props.showInsertionPoint();
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.props.hideInsertionPoint();

      _reactNative.Dimensions.removeEventListener('change', this.onLayout);
    }
  }, {
    key: "calculateMinItemWidth",
    value: function calculateMinItemWidth(bottomSheetWidth) {
      var _styles$columnPadding = _style.default.columnPadding,
          paddingLeft = _styles$columnPadding.paddingLeft,
          paddingRight = _styles$columnPadding.paddingRight;
      return (bottomSheetWidth - 2 * (paddingLeft + paddingRight)) / MIN_COL_NUM;
    }
  }, {
    key: "calculateItemWidth",
    value: function calculateItemWidth() {
      var _InserterButton$Style = _components.InserterButton.Styles.modalItem,
          itemPaddingLeft = _InserterButton$Style.paddingLeft,
          itemPaddingRight = _InserterButton$Style.paddingRight;
      var itemWidth = _components.InserterButton.Styles.modalIconWrapper.width;
      return itemWidth + itemPaddingLeft + itemPaddingRight;
    }
  }, {
    key: "calculateColumnsProperties",
    value: function calculateColumnsProperties() {
      var bottomSheetWidth = _components.BottomSheet.getWidth();

      var _styles$columnPadding2 = _style.default.columnPadding,
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
      return (0, _element.createElement)(_components.InserterButton, {
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
      return (0, _element.createElement)(_components.BottomSheet, {
        isVisible: true,
        onClose: this.onClose,
        hideHeader: true,
        isChildrenScrollable: true
      }, (0, _element.createElement)(_reactNative.TouchableHighlight, {
        accessible: false
      }, (0, _element.createElement)(_components.BottomSheetConsumer, null, function (_ref2) {
        var listProps = _ref2.listProps;
        return (0, _element.createElement)(_reactNative.FlatList, (0, _extends2.default)({
          onLayout: _this2.onLayout,
          key: "InserterUI-".concat(numberOfColumns) //re-render when numberOfColumns changes
          ,
          keyboardShouldPersistTaps: "always",
          numColumns: numberOfColumns,
          data: items,
          ItemSeparatorComponent: function ItemSeparatorComponent() {
            return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
              accessible: false
            }, (0, _element.createElement)(_reactNative.View, {
              style: _style.default.rowSeparator
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
}(_element.Component);

exports.InserterMenu = InserterMenu;

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref3) {
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
  var clipboardBlock = clipboard && (0, _blocks.rawHandler)({
    HTML: clipboard
  })[0];
  var shouldAddClipboardBlock = clipboardBlock && canInsertBlockType(clipboardBlock.name, destinationRootClientId);
  return {
    rootChildBlocks: getChildBlockNames(destinationRootBlockName),
    items: shouldAddClipboardBlock ? [_objectSpread(_objectSpread({}, (0, _lodash.pick)(getBlockType(clipboardBlock.name), ['name', 'icon'])), {}, {
      id: 'clipboard',
      initialAttributes: clipboardBlock.attributes,
      innerBlocks: clipboardBlock.innerBlocks
    })].concat((0, _toConsumableArray2.default)(getInserterItems(destinationRootClientId))) : getInserterItems(destinationRootClientId),
    destinationRootClientId: destinationRootClientId,
    shouldInsertAtTheTop: shouldInsertAtTheTop
  };
}), (0, _data.withDispatch)(function (dispatch, ownProps, _ref4) {
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
      var insertedBlock = (0, _blocks.createBlock)(name, initialAttributes, innerBlocks);
      insertBlock(insertedBlock, ownProps.insertionIndex, ownProps.destinationRootClientId);
      ownProps.onSelect();
    },
    insertDefaultBlock: function insertDefaultBlock() {
      _insertDefaultBlock({}, ownProps.destinationRootClientId, ownProps.insertionIndex);
    }
  };
}), _compose.withInstanceId)(InserterMenu);

exports.default = _default;
//# sourceMappingURL=menu.native.js.map