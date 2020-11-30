import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __experimentalTreeGridCell as TreeGridCell } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockNavigationLeaf from './leaf';
import DescenderLines from './descender-lines';
import Inserter from '../inserter';
export default function BlockNavigationAppender(_ref) {
  var parentBlockClientId = _ref.parentBlockClientId,
      position = _ref.position,
      level = _ref.level,
      rowCount = _ref.rowCount,
      terminatedLevels = _ref.terminatedLevels,
      path = _ref.path;
  var isDragging = useSelect(function (select) {
    var _select = select('core/block-editor'),
        isBlockBeingDragged = _select.isBlockBeingDragged,
        isAncestorBeingDragged = _select.isAncestorBeingDragged;

    return isBlockBeingDragged(parentBlockClientId) || isAncestorBeingDragged(parentBlockClientId);
  }, [parentBlockClientId]);
  var instanceId = useInstanceId(BlockNavigationAppender);
  var descriptionId = "block-navigation-appender-row__description_".concat(instanceId);
  var appenderPositionDescription = sprintf(
  /* translators: 1: The numerical position of the block that will be inserted. 2: The level of nesting for the block that will be inserted. */
  __('Add block at position %1$d, Level %2$d'), position, level);
  return createElement(BlockNavigationLeaf, {
    className: classnames({
      'is-dragging': isDragging
    }),
    level: level,
    position: position,
    rowCount: rowCount,
    path: path
  }, createElement(TreeGridCell, {
    className: "block-editor-block-navigation-appender__cell",
    colSpan: "3"
  }, function (_ref2) {
    var ref = _ref2.ref,
        tabIndex = _ref2.tabIndex,
        onFocus = _ref2.onFocus;
    return createElement("div", {
      className: "block-editor-block-navigation-appender__container"
    }, createElement(DescenderLines, {
      level: level,
      isLastRow: position === rowCount,
      terminatedLevels: terminatedLevels
    }), createElement(Inserter, {
      rootClientId: parentBlockClientId,
      __experimentalIsQuick: true,
      __experimentalSelectBlockOnInsert: false,
      "aria-describedby": descriptionId,
      toggleProps: {
        ref: ref,
        tabIndex: tabIndex,
        onFocus: onFocus
      }
    }), createElement("div", {
      className: "block-editor-block-navigation-appender__description",
      id: descriptionId
    }, appenderPositionDescription));
  }));
}
//# sourceMappingURL=appender.js.map