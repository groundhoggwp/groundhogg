import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
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
import { View, Text, TouchableWithoutFeedback, Dimensions } from 'react-native';
/**
 * WordPress dependencies
 */

import { Component, createRef } from '@wordpress/element';
import { GlobalStylesContext, WIDE_ALIGNMENTS } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { getBlockType, __experimentalGetAccessibleBlockLabel as getAccessibleBlockLabel } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import styles from './block.scss';
import BlockEdit from '../block-edit';
import BlockInvalidWarning from './block-invalid-warning';
import BlockMobileToolbar from '../block-mobile-toolbar';

var BlockListBlock = /*#__PURE__*/function (_Component) {
  _inherits(BlockListBlock, _Component);

  var _super = _createSuper(BlockListBlock);

  function BlockListBlock() {
    var _this;

    _classCallCheck(this, BlockListBlock);

    _this = _super.apply(this, arguments);
    _this.insertBlocksAfter = _this.insertBlocksAfter.bind(_assertThisInitialized(_this));
    _this.onFocus = _this.onFocus.bind(_assertThisInitialized(_this));
    _this.getBlockWidth = _this.getBlockWidth.bind(_assertThisInitialized(_this));
    _this.state = {
      blockWidth: 0
    };
    _this.anchorNodeRef = createRef();
    return _this;
  }

  _createClass(BlockListBlock, [{
    key: "onFocus",
    value: function onFocus() {
      var _this$props = this.props,
          firstToSelectId = _this$props.firstToSelectId,
          isSelected = _this$props.isSelected,
          onSelect = _this$props.onSelect;

      if (!isSelected) {
        onSelect(firstToSelectId);
      }
    }
  }, {
    key: "insertBlocksAfter",
    value: function insertBlocksAfter(blocks) {
      this.props.onInsertBlocks(blocks, this.props.order + 1);

      if (blocks[0]) {
        // focus on the first block inserted
        this.props.onSelect(blocks[0].clientId);
      }
    }
  }, {
    key: "getBlockWidth",
    value: function getBlockWidth(_ref) {
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
    key: "getBlockForType",
    value: function getBlockForType() {
      var _this2 = this;

      return createElement(GlobalStylesContext.Consumer, null, function (globalStyle) {
        var mergedStyle = _objectSpread(_objectSpread({}, globalStyle), _this2.props.wrapperProps.style);

        return createElement(GlobalStylesContext.Provider, {
          value: mergedStyle
        }, createElement(BlockEdit, {
          name: _this2.props.name,
          isSelected: _this2.props.isSelected,
          attributes: _this2.props.attributes,
          setAttributes: _this2.props.onChange,
          onFocus: _this2.onFocus,
          onReplace: _this2.props.onReplace,
          insertBlocksAfter: _this2.insertBlocksAfter,
          mergeBlocks: _this2.props.mergeBlocks,
          onCaretVerticalPositionChange: _this2.props.onCaretVerticalPositionChange // Block level styles
          ,
          wrapperProps: _this2.props.wrapperProps // inherited styles merged with block level styles
          ,
          mergedStyle: mergedStyle,
          clientId: _this2.props.clientId,
          parentWidth: _this2.props.parentWidth,
          contentStyle: _this2.props.contentStyle,
          onDeleteBlock: _this2.props.onDeleteBlock
        }), createElement(View, {
          onLayout: _this2.getBlockWidth
        }));
      });
    }
  }, {
    key: "renderBlockTitle",
    value: function renderBlockTitle() {
      return createElement(View, {
        style: styles.blockTitle
      }, createElement(Text, null, "BlockType: ", this.props.name));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          attributes = _this$props2.attributes,
          blockType = _this$props2.blockType,
          clientId = _this$props2.clientId,
          icon = _this$props2.icon,
          isSelected = _this$props2.isSelected,
          isValid = _this$props2.isValid,
          order = _this$props2.order,
          title = _this$props2.title,
          isDimmed = _this$props2.isDimmed,
          isTouchable = _this$props2.isTouchable,
          onDeleteBlock = _this$props2.onDeleteBlock,
          isStackedHorizontally = _this$props2.isStackedHorizontally,
          isParentSelected = _this$props2.isParentSelected,
          getStylesFromColorScheme = _this$props2.getStylesFromColorScheme,
          marginVertical = _this$props2.marginVertical,
          marginHorizontal = _this$props2.marginHorizontal,
          isInnerBlockSelected = _this$props2.isInnerBlockSelected;

      if (!attributes || !blockType) {
        return null;
      }

      var blockWidth = this.state.blockWidth;
      var align = attributes.align;
      var accessibilityLabel = getAccessibleBlockLabel(blockType, attributes, order + 1);
      var accessible = !(isSelected || isInnerBlockSelected);
      var isFullWidth = align === WIDE_ALIGNMENTS.alignments.full;
      var screenWidth = Math.floor(Dimensions.get('window').width);
      return createElement(TouchableWithoutFeedback, {
        onPress: this.onFocus,
        accessible: accessible,
        accessibilityRole: 'button'
      }, createElement(View, {
        style: {
          flex: 1
        },
        accessibilityLabel: accessibilityLabel
      }, createElement(View, {
        pointerEvents: isTouchable ? 'auto' : 'box-only',
        accessibilityLabel: accessibilityLabel,
        style: [{
          marginVertical: marginVertical,
          marginHorizontal: marginHorizontal,
          flex: 1
        }, isDimmed && styles.dimmed]
      }, isSelected && createElement(View, {
        style: [styles.solidBorder, isFullWidth && blockWidth < screenWidth && styles.borderFullWidth, getStylesFromColorScheme(styles.solidBorderColor, styles.solidBorderColorDark)]
      }), isParentSelected && createElement(View, {
        style: [styles.dashedBorder, getStylesFromColorScheme(styles.dashedBorderColor, styles.dashedBorderColorDark)]
      }), isValid ? this.getBlockForType() : createElement(BlockInvalidWarning, {
        blockTitle: title,
        icon: icon
      }), createElement(View, {
        style: styles.neutralToolbar,
        ref: this.anchorNodeRef
      }, isSelected && createElement(BlockMobileToolbar, {
        clientId: clientId,
        onDelete: onDeleteBlock,
        isStackedHorizontally: isStackedHorizontally,
        blockWidth: blockWidth,
        anchorNodeRef: this.anchorNodeRef.current,
        isFullWidth: isFullWidth
      })))));
    }
  }]);

  return BlockListBlock;
}(Component); // Helper function to memoize the wrapperProps since getEditWrapperProps always returns a new reference


var wrapperPropsCache = new WeakMap();
var emptyObj = {};

function getWrapperProps(value, getWrapperPropsFunction) {
  if (!getWrapperPropsFunction) {
    return emptyObj;
  }

  var cachedValue = wrapperPropsCache.get(value);

  if (!cachedValue) {
    var wrapperProps = getWrapperPropsFunction(value);
    wrapperPropsCache.set(value, wrapperProps);
    return wrapperProps;
  }

  return cachedValue;
}

export default compose([withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId,
      rootClientId = _ref2.rootClientId;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex,
      isBlockSelected = _select.isBlockSelected,
      __unstableGetBlockWithoutInnerBlocks = _select.__unstableGetBlockWithoutInnerBlocks,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getLowestCommonAncestorWithSelectedBlock = _select.getLowestCommonAncestorWithSelectedBlock,
      getBlockParents = _select.getBlockParents,
      hasSelectedInnerBlock = _select.hasSelectedInnerBlock;

  var order = getBlockIndex(clientId, rootClientId);
  var isSelected = isBlockSelected(clientId);
  var isInnerBlockSelected = hasSelectedInnerBlock(clientId);

  var block = __unstableGetBlockWithoutInnerBlocks(clientId);

  var _ref3 = block || {},
      name = _ref3.name,
      attributes = _ref3.attributes,
      isValid = _ref3.isValid;

  var blockType = getBlockType(name || 'core/missing');
  var title = blockType.title;
  var icon = blockType.icon;
  var parents = getBlockParents(clientId, true);
  var parentId = parents[0] || '';
  var selectedBlockClientId = getSelectedBlockClientId();
  var commonAncestor = getLowestCommonAncestorWithSelectedBlock(clientId);
  var commonAncestorIndex = parents.indexOf(commonAncestor) - 1;
  var firstToSelectId = commonAncestor ? parents[commonAncestorIndex] : parents[parents.length - 1];
  var isParentSelected = // set false as a default value to prevent re-render when it's changed from null to false
  (selectedBlockClientId || false) && selectedBlockClientId === parentId;
  var selectedParents = selectedBlockClientId ? getBlockParents(selectedBlockClientId) : [];
  var isDescendantOfParentSelected = selectedParents.includes(parentId);
  var isTouchable = isSelected || isDescendantOfParentSelected || isParentSelected || parentId === '';
  return {
    icon: icon,
    name: name || 'core/missing',
    order: order,
    title: title,
    attributes: attributes,
    blockType: blockType,
    isSelected: isSelected,
    isInnerBlockSelected: isInnerBlockSelected,
    isValid: isValid,
    isParentSelected: isParentSelected,
    firstToSelectId: firstToSelectId,
    isTouchable: isTouchable,
    wrapperProps: getWrapperProps(attributes, blockType.getEditWrapperProps)
  };
}), withDispatch(function (dispatch, ownProps, _ref4) {
  var select = _ref4.select;

  var _dispatch = dispatch('core/block-editor'),
      insertBlocks = _dispatch.insertBlocks,
      _mergeBlocks = _dispatch.mergeBlocks,
      replaceBlocks = _dispatch.replaceBlocks,
      selectBlock = _dispatch.selectBlock,
      updateBlockAttributes = _dispatch.updateBlockAttributes;

  return {
    mergeBlocks: function mergeBlocks(forward) {
      var clientId = ownProps.clientId;

      var _select2 = select('core/block-editor'),
          getPreviousBlockClientId = _select2.getPreviousBlockClientId,
          getNextBlockClientId = _select2.getNextBlockClientId;

      if (forward) {
        var nextBlockClientId = getNextBlockClientId(clientId);

        if (nextBlockClientId) {
          _mergeBlocks(clientId, nextBlockClientId);
        }
      } else {
        var previousBlockClientId = getPreviousBlockClientId(clientId);

        if (previousBlockClientId) {
          _mergeBlocks(previousBlockClientId, clientId);
        }
      }
    },
    onInsertBlocks: function onInsertBlocks(blocks, index) {
      insertBlocks(blocks, index, ownProps.rootClientId);
    },
    onSelect: function onSelect() {
      var clientId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : ownProps.clientId;
      var initialPosition = arguments.length > 1 ? arguments[1] : undefined;
      selectBlock(clientId, initialPosition);
    },
    onChange: function onChange(attributes) {
      updateBlockAttributes(ownProps.clientId, attributes);
    },
    onReplace: function onReplace(blocks, indexToSelect) {
      replaceBlocks([ownProps.clientId], blocks, indexToSelect);
    }
  };
}), withPreferredColorScheme])(BlockListBlock);
//# sourceMappingURL=block.native.js.map