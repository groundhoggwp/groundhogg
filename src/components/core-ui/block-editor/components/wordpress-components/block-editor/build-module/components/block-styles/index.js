import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { find, noop } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { _x } from '@wordpress/i18n';
import { getBlockType, cloneBlock, getBlockFromExample } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { getActiveStyle, replaceActiveStyle } from './utils';
import BlockPreview from '../block-preview';

var useGenericPreviewBlock = function useGenericPreviewBlock(block, type) {
  return useMemo(function () {
    return type.example ? getBlockFromExample(block.name, {
      attributes: type.example.attributes,
      innerBlocks: type.example.innerBlocks
    }) : cloneBlock(block);
  }, [type.example ? block.name : block, type]);
};

function BlockStyles(_ref) {
  var clientId = _ref.clientId,
      _ref$onSwitch = _ref.onSwitch,
      onSwitch = _ref$onSwitch === void 0 ? noop : _ref$onSwitch,
      _ref$onHoverClassName = _ref.onHoverClassName,
      onHoverClassName = _ref$onHoverClassName === void 0 ? noop : _ref$onHoverClassName,
      itemRole = _ref.itemRole;

  var selector = function selector(select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock;

    var _select2 = select('core/blocks'),
        getBlockStyles = _select2.getBlockStyles;

    var block = getBlock(clientId);
    var blockType = getBlockType(block.name);
    return {
      block: block,
      type: blockType,
      styles: getBlockStyles(block.name),
      className: block.attributes.className || ''
    };
  };

  var _useSelect = useSelect(selector, [clientId]),
      styles = _useSelect.styles,
      block = _useSelect.block,
      type = _useSelect.type,
      className = _useSelect.className;

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var genericPreviewBlock = useGenericPreviewBlock(block, type);

  if (!styles || styles.length === 0) {
    return null;
  }

  var renderedStyles = find(styles, 'isDefault') ? styles : [{
    name: 'default',
    label: _x('Default', 'block style'),
    isDefault: true
  }].concat(_toConsumableArray(styles));
  var activeStyle = getActiveStyle(renderedStyles, className);
  return createElement("div", {
    className: "block-editor-block-styles"
  }, renderedStyles.map(function (style) {
    var styleClassName = replaceActiveStyle(className, activeStyle, style);
    return createElement(BlockStyleItem, {
      genericPreviewBlock: genericPreviewBlock,
      className: className,
      isActive: activeStyle === style,
      key: style.name,
      onSelect: function onSelect() {
        updateBlockAttributes(clientId, {
          className: styleClassName
        });
        onHoverClassName(null);
        onSwitch();
      },
      onBlur: function onBlur() {
        return onHoverClassName(null);
      },
      onHover: function onHover() {
        return onHoverClassName(styleClassName);
      },
      style: style,
      styleClassName: styleClassName,
      itemRole: itemRole
    });
  }));
}

function BlockStyleItem(_ref2) {
  var genericPreviewBlock = _ref2.genericPreviewBlock,
      style = _ref2.style,
      isActive = _ref2.isActive,
      onBlur = _ref2.onBlur,
      onHover = _ref2.onHover,
      onSelect = _ref2.onSelect,
      styleClassName = _ref2.styleClassName,
      itemRole = _ref2.itemRole;
  var previewBlocks = useMemo(function () {
    return _objectSpread(_objectSpread({}, genericPreviewBlock), {}, {
      attributes: _objectSpread(_objectSpread({}, genericPreviewBlock.attributes), {}, {
        className: styleClassName
      })
    });
  }, [genericPreviewBlock, styleClassName]);
  return createElement("div", {
    key: style.name,
    className: classnames('block-editor-block-styles__item', {
      'is-active': isActive
    }),
    onClick: function onClick() {
      return onSelect();
    },
    onKeyDown: function onKeyDown(event) {
      if (ENTER === event.keyCode || SPACE === event.keyCode) {
        event.preventDefault();
        onSelect();
      }
    },
    onMouseEnter: onHover,
    onMouseLeave: onBlur,
    role: itemRole || 'button',
    tabIndex: "0",
    "aria-label": style.label || style.name
  }, createElement("div", {
    className: "block-editor-block-styles__item-preview"
  }, createElement(BlockPreview, {
    viewportWidth: 500,
    blocks: previewBlocks
  })), createElement("div", {
    className: "block-editor-block-styles__item-label"
  }, style.label || style.name));
}

export default BlockStyles;
//# sourceMappingURL=index.js.map