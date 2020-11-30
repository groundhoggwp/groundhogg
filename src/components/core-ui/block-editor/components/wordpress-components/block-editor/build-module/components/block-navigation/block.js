import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __experimentalTreeGridCell as TreeGridCell, __experimentalTreeGridItem as TreeGridItem, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { useState, useRef, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockNavigationLeaf from './leaf';
import { BlockMoverUpButton, BlockMoverDownButton } from '../block-mover/button';
import DescenderLines from './descender-lines';
import BlockNavigationBlockContents from './block-contents';
import BlockSettingsDropdown from '../block-settings-menu/block-settings-dropdown';
import { useBlockNavigationContext } from './context';
export default function BlockNavigationBlock(_ref) {
  var block = _ref.block,
      isSelected = _ref.isSelected,
      _onClick = _ref.onClick,
      position = _ref.position,
      level = _ref.level,
      rowCount = _ref.rowCount,
      siblingBlockCount = _ref.siblingBlockCount,
      showBlockMovers = _ref.showBlockMovers,
      terminatedLevels = _ref.terminatedLevels,
      path = _ref.path;
  var cellRef = useRef(null);

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isHovered = _useState2[0],
      setIsHovered = _useState2[1];

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isFocused = _useState4[0],
      setIsFocused = _useState4[1];

  var clientId = block.clientId;
  var isDragging = useSelect(function (select) {
    var _select = select('core/block-editor'),
        isBlockBeingDragged = _select.isBlockBeingDragged,
        isAncestorBeingDragged = _select.isAncestorBeingDragged;

    return isBlockBeingDragged(clientId) || isAncestorBeingDragged(clientId);
  }, [clientId]);

  var _useDispatch = useDispatch('core/block-editor'),
      selectEditorBlock = _useDispatch.selectBlock;

  var hasSiblings = siblingBlockCount > 0;
  var hasRenderedMovers = showBlockMovers && hasSiblings;
  var hasVisibleMovers = isHovered || isFocused;
  var moverCellClassName = classnames('block-editor-block-navigation-block__mover-cell', {
    'is-visible': hasVisibleMovers
  });

  var _useBlockNavigationCo = useBlockNavigationContext(),
      withExperimentalFeatures = _useBlockNavigationCo.__experimentalFeatures;

  var blockNavigationBlockSettingsClassName = classnames('block-editor-block-navigation-block__menu-cell', {
    'is-visible': hasVisibleMovers
  });
  useEffect(function () {
    if (withExperimentalFeatures && isSelected) {
      cellRef.current.focus();
    }
  }, [withExperimentalFeatures, isSelected]);
  return createElement(BlockNavigationLeaf, {
    className: classnames({
      'is-selected': isSelected,
      'is-dragging': isDragging
    }),
    onMouseEnter: function onMouseEnter() {
      return setIsHovered(true);
    },
    onMouseLeave: function onMouseLeave() {
      return setIsHovered(false);
    },
    onFocus: function onFocus() {
      return setIsFocused(true);
    },
    onBlur: function onBlur() {
      return setIsFocused(false);
    },
    level: level,
    position: position,
    rowCount: rowCount,
    path: path,
    id: "block-navigation-block-".concat(clientId),
    "data-block": clientId
  }, createElement(TreeGridCell, {
    className: "block-editor-block-navigation-block__contents-cell",
    colSpan: hasRenderedMovers ? undefined : 2,
    ref: cellRef
  }, function (_ref2) {
    var ref = _ref2.ref,
        tabIndex = _ref2.tabIndex,
        onFocus = _ref2.onFocus;
    return createElement("div", {
      className: "block-editor-block-navigation-block__contents-container"
    }, createElement(DescenderLines, {
      level: level,
      isLastRow: position === rowCount,
      terminatedLevels: terminatedLevels
    }), createElement(BlockNavigationBlockContents, {
      block: block,
      onClick: function onClick() {
        return _onClick(block.clientId);
      },
      isSelected: isSelected,
      position: position,
      siblingBlockCount: siblingBlockCount,
      level: level,
      ref: ref,
      tabIndex: tabIndex,
      onFocus: onFocus
    }));
  }), hasRenderedMovers && createElement(Fragment, null, createElement(TreeGridCell, {
    className: moverCellClassName,
    withoutGridItem: true
  }, createElement(TreeGridItem, null, function (_ref3) {
    var ref = _ref3.ref,
        tabIndex = _ref3.tabIndex,
        onFocus = _ref3.onFocus;
    return createElement(BlockMoverUpButton, {
      orientation: "vertical",
      clientIds: [clientId],
      ref: ref,
      tabIndex: tabIndex,
      onFocus: onFocus
    });
  }), createElement(TreeGridItem, null, function (_ref4) {
    var ref = _ref4.ref,
        tabIndex = _ref4.tabIndex,
        onFocus = _ref4.onFocus;
    return createElement(BlockMoverDownButton, {
      orientation: "vertical",
      clientIds: [clientId],
      ref: ref,
      tabIndex: tabIndex,
      onFocus: onFocus
    });
  }))), withExperimentalFeatures && createElement(TreeGridCell, {
    className: blockNavigationBlockSettingsClassName
  }, function (_ref5) {
    var ref = _ref5.ref,
        tabIndex = _ref5.tabIndex,
        onFocus = _ref5.onFocus;
    return createElement(BlockSettingsDropdown, {
      clientIds: [clientId],
      icon: moreVertical,
      toggleProps: {
        ref: ref,
        tabIndex: tabIndex,
        onFocus: onFocus
      },
      disableOpenOnArrowDown: true,
      __experimentalSelectBlock: _onClick
    }, function (_ref6) {
      var onClose = _ref6.onClose;
      return createElement(MenuGroup, null, createElement(MenuItem, {
        onClick: /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
          return _regeneratorRuntime.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  _context.next = 2;
                  return selectEditorBlock(null);

                case 2:
                  _context.next = 4;
                  return selectEditorBlock(clientId);

                case 4:
                  onClose();

                case 5:
                case "end":
                  return _context.stop();
              }
            }
          }, _callee);
        }))
      }, __('Go to block')));
    });
  }));
}
//# sourceMappingURL=block.js.map