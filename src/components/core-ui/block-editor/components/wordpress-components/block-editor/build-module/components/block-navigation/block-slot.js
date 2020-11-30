import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { getBlockType } from '@wordpress/blocks';
import { Fill, Slot, VisuallyHidden } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { Children, cloneElement, forwardRef, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
import { BlockListBlockContext } from '../block-list/block';
import BlockNavigationBlockSelectButton from './block-select-button';
import { getBlockPositionDescription } from './utils';

var getSlotName = function getSlotName(clientId) {
  return "BlockNavigationBlock-".concat(clientId);
};

function BlockNavigationBlockSlot(props, ref) {
  var instanceId = useInstanceId(BlockNavigationBlockSlot);
  var clientId = props.block.clientId;
  return createElement(Slot, {
    name: getSlotName(clientId)
  }, function (fills) {
    if (!fills.length) {
      return createElement(BlockNavigationBlockSelectButton, _extends({
        ref: ref
      }, props));
    }

    var className = props.className,
        block = props.block,
        isSelected = props.isSelected,
        position = props.position,
        siblingBlockCount = props.siblingBlockCount,
        level = props.level,
        tabIndex = props.tabIndex,
        onFocus = props.onFocus;
    var name = block.name;
    var blockType = getBlockType(name);
    var descriptionId = "block-navigation-block-slot__".concat(instanceId);
    var blockPositionDescription = getBlockPositionDescription(position, siblingBlockCount, level);
    var forwardedFillProps = {
      // Ensure that the component in the slot can receive
      // keyboard navigation.
      tabIndex: tabIndex,
      onFocus: onFocus,
      ref: ref,
      // Give the element rendered in the slot a description
      // that describes its position.
      'aria-describedby': descriptionId
    };
    return createElement(Fragment, null, createElement("div", {
      className: classnames('block-editor-block-navigation-block-slot', className)
    }, createElement(BlockIcon, {
      icon: blockType.icon,
      showColors: true
    }), Children.map(fills, function (fill) {
      return cloneElement(fill, _objectSpread(_objectSpread({}, fill.props), forwardedFillProps));
    }), isSelected && createElement(VisuallyHidden, null, __('(selected block)')), createElement("div", {
      className: "block-editor-block-navigation-block-slot__description",
      id: descriptionId
    }, blockPositionDescription)));
  });
}

export default forwardRef(BlockNavigationBlockSlot);
export var BlockNavigationBlockFill = function BlockNavigationBlockFill(props) {
  var _useContext = useContext(BlockListBlockContext),
      clientId = _useContext.clientId;

  return createElement(Fill, _extends({}, props, {
    name: getSlotName(clientId)
  }));
};
//# sourceMappingURL=block-slot.js.map