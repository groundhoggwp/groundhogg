"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.BlockSwitcher = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blocks2 = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

var _blockStyles = _interopRequireDefault(require("../block-styles"));

var _blockPreview = _interopRequireDefault(require("../block-preview"));

var _blockTransformationsMenu = _interopRequireDefault(require("./block-transformations-menu"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PreviewBlockPopover(_ref) {
  var hoveredBlock = _ref.hoveredBlock,
      hoveredClassName = _ref.hoveredClassName;
  var hoveredBlockType = (0, _blocks2.getBlockType)(hoveredBlock.name);
  return (0, _element.createElement)("div", {
    className: "block-editor-block-switcher__popover__preview__parent"
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-switcher__popover__preview__container"
  }, (0, _element.createElement)(_components.Popover, {
    className: "block-editor-block-switcher__preview__popover",
    position: "bottom right",
    focusOnMount: false
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-switcher__preview"
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-switcher__preview-title"
  }, (0, _i18n.__)('Preview')), (0, _element.createElement)(_blockPreview.default, {
    viewportWidth: 500,
    blocks: hoveredBlockType.example ? (0, _blocks2.getBlockFromExample)(hoveredBlock.name, {
      attributes: _objectSpread(_objectSpread({}, hoveredBlockType.example.attributes), {}, {
        className: hoveredClassName
      }),
      innerBlocks: hoveredBlockType.example.innerBlocks
    }) : (0, _blocks2.cloneBlock)(hoveredBlock, {
      className: hoveredClassName
    })
  })))));
}

var BlockSwitcher = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(BlockSwitcher, _Component);

  var _super = _createSuper(BlockSwitcher);

  function BlockSwitcher() {
    var _this;

    (0, _classCallCheck2.default)(this, BlockSwitcher);
    _this = _super.apply(this, arguments);
    _this.state = {
      hoveredClassName: null
    };
    _this.onHoverClassName = _this.onHoverClassName.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(BlockSwitcher, [{
    key: "onHoverClassName",
    value: function onHoverClassName(className) {
      this.setState({
        hoveredClassName: className
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          blocks = _this$props.blocks,
          onTransform = _this$props.onTransform,
          inserterItems = _this$props.inserterItems,
          hasBlockStyles = _this$props.hasBlockStyles;
      var hoveredClassName = this.state.hoveredClassName;

      if (!Array.isArray(blocks) || !blocks.length) {
        return null;
      }

      var _blocks = (0, _slicedToArray2.default)(blocks, 1),
          hoveredBlock = _blocks[0];

      var itemsByName = (0, _lodash.mapKeys)(inserterItems, function (_ref2) {
        var name = _ref2.name;
        return name;
      });
      var possibleBlockTransformations = (0, _lodash.orderBy)((0, _lodash.filter)((0, _blocks2.getPossibleBlockTransformations)(blocks), function (block) {
        return block && !!itemsByName[block.name];
      }), function (block) {
        return itemsByName[block.name].frecency;
      }, 'desc'); // When selection consists of blocks of multiple types, display an
      // appropriate icon to communicate the non-uniformity.

      var isSelectionOfSameType = (0, _lodash.uniq)((0, _lodash.map)(blocks, 'name')).length === 1;
      var icon;

      if (isSelectionOfSameType) {
        var sourceBlockName = hoveredBlock.name;
        var blockType = (0, _blocks2.getBlockType)(sourceBlockName);
        icon = blockType.icon;
      } else {
        icon = _icons.stack;
      }

      var hasPossibleBlockTransformations = !!possibleBlockTransformations.length;

      if (!hasBlockStyles && !hasPossibleBlockTransformations) {
        return (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
          disabled: true,
          className: "block-editor-block-switcher__no-switcher-icon",
          title: (0, _i18n.__)('Block icon'),
          icon: (0, _element.createElement)(_blockIcon.default, {
            icon: icon,
            showColors: true
          })
        }));
      }

      var blockSwitcherLabel = 1 === blocks.length ? (0, _i18n.__)('Change block type or style') : (0, _i18n.sprintf)(
      /* translators: %s: number of blocks. */
      (0, _i18n._n)('Change type of %d block', 'Change type of %d blocks', blocks.length), blocks.length);
      return (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarItem, null, function (toggleProps) {
        return (0, _element.createElement)(_components.DropdownMenu, {
          className: "block-editor-block-switcher",
          label: blockSwitcherLabel,
          popoverProps: {
            position: 'bottom right',
            isAlternate: true,
            className: 'block-editor-block-switcher__popover'
          },
          icon: (0, _element.createElement)(_blockIcon.default, {
            icon: icon,
            className: "block-editor-block-switcher__toggle",
            showColors: true
          }),
          toggleProps: toggleProps,
          menuProps: {
            orientation: 'both'
          }
        }, function (_ref3) {
          var onClose = _ref3.onClose;
          return (hasBlockStyles || hasPossibleBlockTransformations) && (0, _element.createElement)("div", {
            className: "block-editor-block-switcher__container"
          }, hasPossibleBlockTransformations && (0, _element.createElement)(_blockTransformationsMenu.default, {
            className: "block-editor-block-switcher__transforms__menugroup",
            possibleBlockTransformations: possibleBlockTransformations,
            onSelect: function onSelect(name) {
              onTransform(blocks, name);
              onClose();
            }
          }), hasBlockStyles && (0, _element.createElement)(_components.MenuGroup, {
            label: (0, _i18n.__)('Styles'),
            className: "block-editor-block-switcher__styles__menugroup"
          }, hoveredClassName !== null && (0, _element.createElement)(PreviewBlockPopover, {
            hoveredBlock: hoveredBlock,
            hoveredClassName: hoveredClassName
          }), (0, _element.createElement)(_blockStyles.default, {
            clientId: hoveredBlock.clientId,
            onSwitch: onClose,
            onHoverClassName: _this2.onHoverClassName,
            itemRole: "menuitem"
          })));
        });
      }));
    }
  }]);
  return BlockSwitcher;
}(_element.Component);

exports.BlockSwitcher = BlockSwitcher;

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref4) {
  var clientIds = _ref4.clientIds;

  var _select = select('core/block-editor'),
      getBlocksByClientId = _select.getBlocksByClientId,
      getBlockRootClientId = _select.getBlockRootClientId,
      getInserterItems = _select.getInserterItems;

  var _select2 = select('core/blocks'),
      getBlockStyles = _select2.getBlockStyles;

  var rootClientId = getBlockRootClientId((0, _lodash.castArray)(clientIds)[0]);
  var blocks = getBlocksByClientId(clientIds);
  var firstBlock = blocks && blocks.length === 1 ? blocks[0] : null;
  var styles = firstBlock && getBlockStyles(firstBlock.name);
  return {
    blocks: blocks,
    inserterItems: getInserterItems(rootClientId),
    hasBlockStyles: styles && styles.length > 0
  };
}), (0, _data.withDispatch)(function (dispatch, ownProps) {
  return {
    onTransform: function onTransform(blocks, name) {
      dispatch('core/block-editor').replaceBlocks(ownProps.clientIds, (0, _blocks2.switchToBlockType)(blocks, name));
    }
  };
}))(BlockSwitcher);

exports.default = _default;
//# sourceMappingURL=index.js.map