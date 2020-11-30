"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationListItem;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockNavigationListItem(_ref) {
  var block = _ref.block,
      onClick = _ref.onClick,
      isSelected = _ref.isSelected,
      WrapperComponent = _ref.wrapperComponent,
      children = _ref.children;
  var blockType = (0, _blocks.getBlockType)(block.name);
  return (0, _element.createElement)("div", {
    className: "block-editor-block-navigation__list-item"
  }, (0, _element.createElement)(WrapperComponent, {
    className: (0, _classnames.default)('block-editor-block-navigation__list-item-button', {
      'is-selected': isSelected
    }),
    onClick: onClick
  }, (0, _element.createElement)(_blockIcon.default, {
    icon: blockType.icon,
    showColors: true
  }), children ? children : (0, _blocks.__experimentalGetBlockLabel)(blockType, block.attributes), isSelected && (0, _element.createElement)(_components.VisuallyHidden, {
    as: "span"
  }, (0, _i18n.__)('(selected block)'))));
}

BlockNavigationListItem.defaultProps = {
  onClick: function onClick() {},
  wrapperComponent: function wrapperComponent(props) {
    return (0, _element.createElement)(_components.Button, props);
  }
};
//# sourceMappingURL=list-item.js.map