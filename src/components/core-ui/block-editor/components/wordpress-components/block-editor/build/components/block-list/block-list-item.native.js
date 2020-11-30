"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.BlockListItem = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _block = _interopRequireDefault(require("./block"));

var _insertionPoint = _interopRequireDefault(require("./insertion-point"));

var _blockListItemNative = _interopRequireDefault(require("./block-list-item.native.scss"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var stretchStyle = {
  flex: 1
};

var BlockListItem = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(BlockListItem, _Component);

  var _super = _createSuper(BlockListItem);

  function BlockListItem() {
    var _this;

    (0, _classCallCheck2.default)(this, BlockListItem);
    _this = _super.apply(this, arguments);
    _this.onLayout = _this.onLayout.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      blockWidth: 0
    };
    return _this;
  }

  (0, _createClass2.default)(BlockListItem, [{
    key: "onLayout",
    value: function onLayout(_ref) {
      var nativeEvent = _ref.nativeEvent;
      var layout = nativeEvent.layout;
      var blockWidth = this.state.blockWidth;

      if (blockWidth !== layout.width) {
        this.setState({
          blockWidth: layout.width
        });
      }
    }
  }, {
    key: "getMarginHorizontal",
    value: function getMarginHorizontal() {
      var _this$props = this.props,
          blockAlignment = _this$props.blockAlignment,
          marginHorizontal = _this$props.marginHorizontal,
          parentBlockAlignment = _this$props.parentBlockAlignment;
      var blockWidth = this.state.blockWidth;

      if (blockAlignment === _components.WIDE_ALIGNMENTS.alignments.full) {
        return 0;
      }

      if (blockAlignment === _components.WIDE_ALIGNMENTS.alignments.wide) {
        return marginHorizontal;
      }

      if (parentBlockAlignment === _components.WIDE_ALIGNMENTS.alignments.full && blockWidth <= _components.ALIGNMENT_BREAKPOINTS.medium) {
        return marginHorizontal * 2;
      }

      return marginHorizontal;
    }
  }, {
    key: "getContentStyles",
    value: function getContentStyles(readableContentViewStyle) {
      var _this$props2 = this.props,
          blockAlignment = _this$props2.blockAlignment,
          hasParents = _this$props2.hasParents;
      var isFullWidth = blockAlignment === _components.WIDE_ALIGNMENTS.alignments.full;
      return [readableContentViewStyle, isFullWidth && !hasParents && {
        width: _blockListItemNative.default.fullAlignment.width
      }, isFullWidth && hasParents && {
        paddingHorizontal: _blockListItemNative.default.fullAlignmentPadding.paddingLeft
      }];
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          blockAlignment = _this$props3.blockAlignment,
          clientId = _this$props3.clientId,
          isReadOnly = _this$props3.isReadOnly,
          shouldShowInsertionPointBefore = _this$props3.shouldShowInsertionPointBefore,
          shouldShowInsertionPointAfter = _this$props3.shouldShowInsertionPointAfter,
          contentResizeMode = _this$props3.contentResizeMode,
          shouldShowInnerBlockAppender = _this$props3.shouldShowInnerBlockAppender,
          restProps = (0, _objectWithoutProperties2.default)(_this$props3, ["blockAlignment", "clientId", "isReadOnly", "shouldShowInsertionPointBefore", "shouldShowInsertionPointAfter", "contentResizeMode", "shouldShowInnerBlockAppender"]);
      var readableContentViewStyle = contentResizeMode === 'stretch' && stretchStyle;
      return (0, _element.createElement)(_components.ReadableContentView, {
        align: blockAlignment,
        style: readableContentViewStyle
      }, (0, _element.createElement)(_reactNative.View, {
        style: this.getContentStyles(readableContentViewStyle),
        pointerEvents: isReadOnly ? 'box-only' : 'auto',
        onLayout: this.onLayout
      }, shouldShowInsertionPointBefore && (0, _element.createElement)(_insertionPoint.default, null), (0, _element.createElement)(_block.default, (0, _extends2.default)({
        key: clientId,
        showTitle: false,
        clientId: clientId
      }, restProps, {
        marginHorizontal: this.getMarginHorizontal()
      })), !shouldShowInnerBlockAppender() && shouldShowInsertionPointAfter && (0, _element.createElement)(_insertionPoint.default, null)));
    }
  }]);
  return BlockListItem;
}(_element.Component);

exports.BlockListItem = BlockListItem;

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
  var rootClientId = _ref2.rootClientId,
      isStackedHorizontally = _ref2.isStackedHorizontally,
      clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder,
      getBlockInsertionPoint = _select.getBlockInsertionPoint,
      isBlockInsertionPointVisible = _select.isBlockInsertionPointVisible,
      getSettings = _select.getSettings,
      getBlockParents = _select.getBlockParents,
      __unstableGetBlockWithoutInnerBlocks = _select.__unstableGetBlockWithoutInnerBlocks;

  var blockClientIds = getBlockOrder(rootClientId);
  var insertionPoint = getBlockInsertionPoint();
  var blockInsertionPointIsVisible = isBlockInsertionPointVisible();
  var shouldShowInsertionPointBefore = !isStackedHorizontally && blockInsertionPointIsVisible && insertionPoint.rootClientId === rootClientId && ( // if list is empty, show the insertion point (via the default appender)
  blockClientIds.length === 0 || // or if the insertion point is right before the denoted block
  blockClientIds[insertionPoint.index] === clientId);
  var shouldShowInsertionPointAfter = !isStackedHorizontally && blockInsertionPointIsVisible && insertionPoint.rootClientId === rootClientId && // if the insertion point is at the end of the list
  blockClientIds.length === insertionPoint.index && // and the denoted block is the last one on the list, show the indicator at the end of the block
  blockClientIds[insertionPoint.index - 1] === clientId;
  var isReadOnly = getSettings().readOnly;

  var block = __unstableGetBlockWithoutInnerBlocks(clientId);

  var _ref3 = block || {},
      attributes = _ref3.attributes;

  var _ref4 = attributes || {},
      align = _ref4.align;

  var parents = getBlockParents(clientId, true);
  var hasParents = !!parents.length;
  var parentBlock = hasParents ? __unstableGetBlockWithoutInnerBlocks(parents[0]) : {};

  var _ref5 = (parentBlock === null || parentBlock === void 0 ? void 0 : parentBlock.attributes) || {},
      parentBlockAlignment = _ref5.align;

  return {
    shouldShowInsertionPointBefore: shouldShowInsertionPointBefore,
    shouldShowInsertionPointAfter: shouldShowInsertionPointAfter,
    isReadOnly: isReadOnly,
    hasParents: hasParents,
    blockAlignment: align,
    parentBlockAlignment: parentBlockAlignment
  };
})])(BlockListItem);

exports.default = _default;
//# sourceMappingURL=block-list-item.native.js.map