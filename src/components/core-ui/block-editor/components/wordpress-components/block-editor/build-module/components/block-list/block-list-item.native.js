import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { ReadableContentView, WIDE_ALIGNMENTS, ALIGNMENT_BREAKPOINTS } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockListBlock from './block';
import BlockInsertionPoint from './insertion-point';
import styles from './block-list-item.native.scss';
var stretchStyle = {
  flex: 1
};
export var BlockListItem = /*#__PURE__*/function (_Component) {
  _inherits(BlockListItem, _Component);

  var _super = _createSuper(BlockListItem);

  function BlockListItem() {
    var _this;

    _classCallCheck(this, BlockListItem);

    _this = _super.apply(this, arguments);
    _this.onLayout = _this.onLayout.bind(_assertThisInitialized(_this));
    _this.state = {
      blockWidth: 0
    };
    return _this;
  }

  _createClass(BlockListItem, [{
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

      if (blockAlignment === WIDE_ALIGNMENTS.alignments.full) {
        return 0;
      }

      if (blockAlignment === WIDE_ALIGNMENTS.alignments.wide) {
        return marginHorizontal;
      }

      if (parentBlockAlignment === WIDE_ALIGNMENTS.alignments.full && blockWidth <= ALIGNMENT_BREAKPOINTS.medium) {
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
      var isFullWidth = blockAlignment === WIDE_ALIGNMENTS.alignments.full;
      return [readableContentViewStyle, isFullWidth && !hasParents && {
        width: styles.fullAlignment.width
      }, isFullWidth && hasParents && {
        paddingHorizontal: styles.fullAlignmentPadding.paddingLeft
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
          restProps = _objectWithoutProperties(_this$props3, ["blockAlignment", "clientId", "isReadOnly", "shouldShowInsertionPointBefore", "shouldShowInsertionPointAfter", "contentResizeMode", "shouldShowInnerBlockAppender"]);

      var readableContentViewStyle = contentResizeMode === 'stretch' && stretchStyle;
      return createElement(ReadableContentView, {
        align: blockAlignment,
        style: readableContentViewStyle
      }, createElement(View, {
        style: this.getContentStyles(readableContentViewStyle),
        pointerEvents: isReadOnly ? 'box-only' : 'auto',
        onLayout: this.onLayout
      }, shouldShowInsertionPointBefore && createElement(BlockInsertionPoint, null), createElement(BlockListBlock, _extends({
        key: clientId,
        showTitle: false,
        clientId: clientId
      }, restProps, {
        marginHorizontal: this.getMarginHorizontal()
      })), !shouldShowInnerBlockAppender() && shouldShowInsertionPointAfter && createElement(BlockInsertionPoint, null)));
    }
  }]);

  return BlockListItem;
}(Component);
export default compose([withSelect(function (select, _ref2) {
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
//# sourceMappingURL=block-list-item.native.js.map