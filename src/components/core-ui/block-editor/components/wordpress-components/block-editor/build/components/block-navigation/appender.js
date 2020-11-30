"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationAppender;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _leaf = _interopRequireDefault(require("./leaf"));

var _descenderLines = _interopRequireDefault(require("./descender-lines"));

var _inserter = _interopRequireDefault(require("../inserter"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockNavigationAppender(_ref) {
  var parentBlockClientId = _ref.parentBlockClientId,
      position = _ref.position,
      level = _ref.level,
      rowCount = _ref.rowCount,
      terminatedLevels = _ref.terminatedLevels,
      path = _ref.path;
  var isDragging = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        isBlockBeingDragged = _select.isBlockBeingDragged,
        isAncestorBeingDragged = _select.isAncestorBeingDragged;

    return isBlockBeingDragged(parentBlockClientId) || isAncestorBeingDragged(parentBlockClientId);
  }, [parentBlockClientId]);
  var instanceId = (0, _compose.useInstanceId)(BlockNavigationAppender);
  var descriptionId = "block-navigation-appender-row__description_".concat(instanceId);
  var appenderPositionDescription = (0, _i18n.sprintf)(
  /* translators: 1: The numerical position of the block that will be inserted. 2: The level of nesting for the block that will be inserted. */
  (0, _i18n.__)('Add block at position %1$d, Level %2$d'), position, level);
  return (0, _element.createElement)(_leaf.default, {
    className: (0, _classnames.default)({
      'is-dragging': isDragging
    }),
    level: level,
    position: position,
    rowCount: rowCount,
    path: path
  }, (0, _element.createElement)(_components.__experimentalTreeGridCell, {
    className: "block-editor-block-navigation-appender__cell",
    colSpan: "3"
  }, function (_ref2) {
    var ref = _ref2.ref,
        tabIndex = _ref2.tabIndex,
        onFocus = _ref2.onFocus;
    return (0, _element.createElement)("div", {
      className: "block-editor-block-navigation-appender__container"
    }, (0, _element.createElement)(_descenderLines.default, {
      level: level,
      isLastRow: position === rowCount,
      terminatedLevels: terminatedLevels
    }), (0, _element.createElement)(_inserter.default, {
      rootClientId: parentBlockClientId,
      __experimentalIsQuick: true,
      __experimentalSelectBlockOnInsert: false,
      "aria-describedby": descriptionId,
      toggleProps: {
        ref: ref,
        tabIndex: tabIndex,
        onFocus: onFocus
      }
    }), (0, _element.createElement)("div", {
      className: "block-editor-block-navigation-appender__description",
      id: descriptionId
    }, appenderPositionDescription));
  }));
}
//# sourceMappingURL=appender.js.map