import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { castArray, filter, mapKeys, orderBy, uniq, map } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { DropdownMenu, ToolbarButton, ToolbarGroup, ToolbarItem, MenuGroup, Popover } from '@wordpress/components';
import { getBlockType, getPossibleBlockTransformations, switchToBlockType, cloneBlock, getBlockFromExample } from '@wordpress/blocks';
import { Component } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { stack } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
import BlockStyles from '../block-styles';
import BlockPreview from '../block-preview';
import BlockTransformationsMenu from './block-transformations-menu';

function PreviewBlockPopover(_ref) {
  var hoveredBlock = _ref.hoveredBlock,
      hoveredClassName = _ref.hoveredClassName;
  var hoveredBlockType = getBlockType(hoveredBlock.name);
  return createElement("div", {
    className: "block-editor-block-switcher__popover__preview__parent"
  }, createElement("div", {
    className: "block-editor-block-switcher__popover__preview__container"
  }, createElement(Popover, {
    className: "block-editor-block-switcher__preview__popover",
    position: "bottom right",
    focusOnMount: false
  }, createElement("div", {
    className: "block-editor-block-switcher__preview"
  }, createElement("div", {
    className: "block-editor-block-switcher__preview-title"
  }, __('Preview')), createElement(BlockPreview, {
    viewportWidth: 500,
    blocks: hoveredBlockType.example ? getBlockFromExample(hoveredBlock.name, {
      attributes: _objectSpread(_objectSpread({}, hoveredBlockType.example.attributes), {}, {
        className: hoveredClassName
      }),
      innerBlocks: hoveredBlockType.example.innerBlocks
    }) : cloneBlock(hoveredBlock, {
      className: hoveredClassName
    })
  })))));
}

export var BlockSwitcher = /*#__PURE__*/function (_Component) {
  _inherits(BlockSwitcher, _Component);

  var _super = _createSuper(BlockSwitcher);

  function BlockSwitcher() {
    var _this;

    _classCallCheck(this, BlockSwitcher);

    _this = _super.apply(this, arguments);
    _this.state = {
      hoveredClassName: null
    };
    _this.onHoverClassName = _this.onHoverClassName.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(BlockSwitcher, [{
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

      var _blocks = _slicedToArray(blocks, 1),
          hoveredBlock = _blocks[0];

      var itemsByName = mapKeys(inserterItems, function (_ref2) {
        var name = _ref2.name;
        return name;
      });
      var possibleBlockTransformations = orderBy(filter(getPossibleBlockTransformations(blocks), function (block) {
        return block && !!itemsByName[block.name];
      }), function (block) {
        return itemsByName[block.name].frecency;
      }, 'desc'); // When selection consists of blocks of multiple types, display an
      // appropriate icon to communicate the non-uniformity.

      var isSelectionOfSameType = uniq(map(blocks, 'name')).length === 1;
      var icon;

      if (isSelectionOfSameType) {
        var sourceBlockName = hoveredBlock.name;
        var blockType = getBlockType(sourceBlockName);
        icon = blockType.icon;
      } else {
        icon = stack;
      }

      var hasPossibleBlockTransformations = !!possibleBlockTransformations.length;

      if (!hasBlockStyles && !hasPossibleBlockTransformations) {
        return createElement(ToolbarGroup, null, createElement(ToolbarButton, {
          disabled: true,
          className: "block-editor-block-switcher__no-switcher-icon",
          title: __('Block icon'),
          icon: createElement(BlockIcon, {
            icon: icon,
            showColors: true
          })
        }));
      }

      var blockSwitcherLabel = 1 === blocks.length ? __('Change block type or style') : sprintf(
      /* translators: %s: number of blocks. */
      _n('Change type of %d block', 'Change type of %d blocks', blocks.length), blocks.length);
      return createElement(ToolbarGroup, null, createElement(ToolbarItem, null, function (toggleProps) {
        return createElement(DropdownMenu, {
          className: "block-editor-block-switcher",
          label: blockSwitcherLabel,
          popoverProps: {
            position: 'bottom right',
            isAlternate: true,
            className: 'block-editor-block-switcher__popover'
          },
          icon: createElement(BlockIcon, {
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
          return (hasBlockStyles || hasPossibleBlockTransformations) && createElement("div", {
            className: "block-editor-block-switcher__container"
          }, hasPossibleBlockTransformations && createElement(BlockTransformationsMenu, {
            className: "block-editor-block-switcher__transforms__menugroup",
            possibleBlockTransformations: possibleBlockTransformations,
            onSelect: function onSelect(name) {
              onTransform(blocks, name);
              onClose();
            }
          }), hasBlockStyles && createElement(MenuGroup, {
            label: __('Styles'),
            className: "block-editor-block-switcher__styles__menugroup"
          }, hoveredClassName !== null && createElement(PreviewBlockPopover, {
            hoveredBlock: hoveredBlock,
            hoveredClassName: hoveredClassName
          }), createElement(BlockStyles, {
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
}(Component);
export default compose(withSelect(function (select, _ref4) {
  var clientIds = _ref4.clientIds;

  var _select = select('core/block-editor'),
      getBlocksByClientId = _select.getBlocksByClientId,
      getBlockRootClientId = _select.getBlockRootClientId,
      getInserterItems = _select.getInserterItems;

  var _select2 = select('core/blocks'),
      getBlockStyles = _select2.getBlockStyles;

  var rootClientId = getBlockRootClientId(castArray(clientIds)[0]);
  var blocks = getBlocksByClientId(clientIds);
  var firstBlock = blocks && blocks.length === 1 ? blocks[0] : null;
  var styles = firstBlock && getBlockStyles(firstBlock.name);
  return {
    blocks: blocks,
    inserterItems: getInserterItems(rootClientId),
    hasBlockStyles: styles && styles.length > 0
  };
}), withDispatch(function (dispatch, ownProps) {
  return {
    onTransform: function onTransform(blocks, name) {
      dispatch('core/block-editor').replaceBlocks(ownProps.clientIds, switchToBlockType(blocks, name));
    }
  };
}))(BlockSwitcher);
//# sourceMappingURL=index.js.map