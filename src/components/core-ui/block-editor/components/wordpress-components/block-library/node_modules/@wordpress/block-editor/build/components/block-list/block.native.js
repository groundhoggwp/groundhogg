"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _block = _interopRequireDefault(require("./block.scss"));

var _blockEdit = _interopRequireDefault(require("../block-edit"));

var _blockInvalidWarning = _interopRequireDefault(require("./block-invalid-warning"));

var _blockMobileToolbar = _interopRequireDefault(require("../block-mobile-toolbar"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var BlockListBlock = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(BlockListBlock, _Component);

  var _super = _createSuper(BlockListBlock);

  function BlockListBlock() {
    var _this;

    (0, _classCallCheck2.default)(this, BlockListBlock);
    _this = _super.apply(this, arguments);
    _this.insertBlocksAfter = _this.insertBlocksAfter.bind((0, _assertThisInitialized2.default)(_this));
    _this.onFocus = _this.onFocus.bind((0, _assertThisInitialized2.default)(_this));
    _this.getBlockWidth = _this.getBlockWidth.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      blockWidth: 0
    };
    _this.anchorNodeRef = (0, _element.createRef)();
    return _this;
  }

  (0, _createClass2.default)(BlockListBlock, [{
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

      return (0, _element.createElement)(_components.GlobalStylesContext.Consumer, null, function (globalStyle) {
        var mergedStyle = _objectSpread(_objectSpread({}, globalStyle), _this2.props.wrapperProps.style);

        return (0, _element.createElement)(_components.GlobalStylesContext.Provider, {
          value: mergedStyle
        }, (0, _element.createElement)(_blockEdit.default, {
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
        }), (0, _element.createElement)(_reactNative.View, {
          onLayout: _this2.getBlockWidth
        }));
      });
    }
  }, {
    key: "renderBlockTitle",
    value: function renderBlockTitle() {
      return (0, _element.createElement)(_reactNative.View, {
        style: _block.default.blockTitle
      }, (0, _element.createElement)(_reactNative.Text, null, "BlockType: ", this.props.name));
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
      var accessibilityLabel = (0, _blocks.__experimentalGetAccessibleBlockLabel)(blockType, attributes, order + 1);
      var accessible = !(isSelected || isInnerBlockSelected);
      var isFullWidth = align === _components.WIDE_ALIGNMENTS.alignments.full;
      var screenWidth = Math.floor(_reactNative.Dimensions.get('window').width);
      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        onPress: this.onFocus,
        accessible: accessible,
        accessibilityRole: 'button'
      }, (0, _element.createElement)(_reactNative.View, {
        style: {
          flex: 1
        },
        accessibilityLabel: accessibilityLabel
      }, (0, _element.createElement)(_reactNative.View, {
        pointerEvents: isTouchable ? 'auto' : 'box-only',
        accessibilityLabel: accessibilityLabel,
        style: [{
          marginVertical: marginVertical,
          marginHorizontal: marginHorizontal,
          flex: 1
        }, isDimmed && _block.default.dimmed]
      }, isSelected && (0, _element.createElement)(_reactNative.View, {
        style: [_block.default.solidBorder, isFullWidth && blockWidth < screenWidth && _block.default.borderFullWidth, getStylesFromColorScheme(_block.default.solidBorderColor, _block.default.solidBorderColorDark)]
      }), isParentSelected && (0, _element.createElement)(_reactNative.View, {
        style: [_block.default.dashedBorder, getStylesFromColorScheme(_block.default.dashedBorderColor, _block.default.dashedBorderColorDark)]
      }), isValid ? this.getBlockForType() : (0, _element.createElement)(_blockInvalidWarning.default, {
        blockTitle: title,
        icon: icon
      }), (0, _element.createElement)(_reactNative.View, {
        style: _block.default.neutralToolbar,
        ref: this.anchorNodeRef
      }, isSelected && (0, _element.createElement)(_blockMobileToolbar.default, {
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
}(_element.Component); // Helper function to memoize the wrapperProps since getEditWrapperProps always returns a new reference


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

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
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

  var blockType = (0, _blocks.getBlockType)(name || 'core/missing');
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
}), (0, _data.withDispatch)(function (dispatch, ownProps, _ref4) {
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
}), _compose.withPreferredColorScheme])(BlockListBlock);

exports.default = _default;
//# sourceMappingURL=block.native.js.map