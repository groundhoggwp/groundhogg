"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationBlock;

var _element = require("@wordpress/element");

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _asyncToGenerator2 = _interopRequireDefault(require("@babel/runtime/helpers/asyncToGenerator"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _data = require("@wordpress/data");

var _leaf = _interopRequireDefault(require("./leaf"));

var _button = require("../block-mover/button");

var _descenderLines = _interopRequireDefault(require("./descender-lines"));

var _blockContents = _interopRequireDefault(require("./block-contents"));

var _blockSettingsDropdown = _interopRequireDefault(require("../block-settings-menu/block-settings-dropdown"));

var _context2 = require("./context");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockNavigationBlock(_ref) {
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
  var cellRef = (0, _element.useRef)(null);

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isHovered = _useState2[0],
      setIsHovered = _useState2[1];

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isFocused = _useState4[0],
      setIsFocused = _useState4[1];

  var clientId = block.clientId;
  var isDragging = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        isBlockBeingDragged = _select.isBlockBeingDragged,
        isAncestorBeingDragged = _select.isAncestorBeingDragged;

    return isBlockBeingDragged(clientId) || isAncestorBeingDragged(clientId);
  }, [clientId]);

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectEditorBlock = _useDispatch.selectBlock;

  var hasSiblings = siblingBlockCount > 0;
  var hasRenderedMovers = showBlockMovers && hasSiblings;
  var hasVisibleMovers = isHovered || isFocused;
  var moverCellClassName = (0, _classnames.default)('block-editor-block-navigation-block__mover-cell', {
    'is-visible': hasVisibleMovers
  });

  var _useBlockNavigationCo = (0, _context2.useBlockNavigationContext)(),
      withExperimentalFeatures = _useBlockNavigationCo.__experimentalFeatures;

  var blockNavigationBlockSettingsClassName = (0, _classnames.default)('block-editor-block-navigation-block__menu-cell', {
    'is-visible': hasVisibleMovers
  });
  (0, _element.useEffect)(function () {
    if (withExperimentalFeatures && isSelected) {
      cellRef.current.focus();
    }
  }, [withExperimentalFeatures, isSelected]);
  return (0, _element.createElement)(_leaf.default, {
    className: (0, _classnames.default)({
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
  }, (0, _element.createElement)(_components.__experimentalTreeGridCell, {
    className: "block-editor-block-navigation-block__contents-cell",
    colSpan: hasRenderedMovers ? undefined : 2,
    ref: cellRef
  }, function (_ref2) {
    var ref = _ref2.ref,
        tabIndex = _ref2.tabIndex,
        onFocus = _ref2.onFocus;
    return (0, _element.createElement)("div", {
      className: "block-editor-block-navigation-block__contents-container"
    }, (0, _element.createElement)(_descenderLines.default, {
      level: level,
      isLastRow: position === rowCount,
      terminatedLevels: terminatedLevels
    }), (0, _element.createElement)(_blockContents.default, {
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
  }), hasRenderedMovers && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.__experimentalTreeGridCell, {
    className: moverCellClassName,
    withoutGridItem: true
  }, (0, _element.createElement)(_components.__experimentalTreeGridItem, null, function (_ref3) {
    var ref = _ref3.ref,
        tabIndex = _ref3.tabIndex,
        onFocus = _ref3.onFocus;
    return (0, _element.createElement)(_button.BlockMoverUpButton, {
      orientation: "vertical",
      clientIds: [clientId],
      ref: ref,
      tabIndex: tabIndex,
      onFocus: onFocus
    });
  }), (0, _element.createElement)(_components.__experimentalTreeGridItem, null, function (_ref4) {
    var ref = _ref4.ref,
        tabIndex = _ref4.tabIndex,
        onFocus = _ref4.onFocus;
    return (0, _element.createElement)(_button.BlockMoverDownButton, {
      orientation: "vertical",
      clientIds: [clientId],
      ref: ref,
      tabIndex: tabIndex,
      onFocus: onFocus
    });
  }))), withExperimentalFeatures && (0, _element.createElement)(_components.__experimentalTreeGridCell, {
    className: blockNavigationBlockSettingsClassName
  }, function (_ref5) {
    var ref = _ref5.ref,
        tabIndex = _ref5.tabIndex,
        onFocus = _ref5.onFocus;
    return (0, _element.createElement)(_blockSettingsDropdown.default, {
      clientIds: [clientId],
      icon: _icons.moreVertical,
      toggleProps: {
        ref: ref,
        tabIndex: tabIndex,
        onFocus: onFocus
      },
      disableOpenOnArrowDown: true,
      __experimentalSelectBlock: _onClick
    }, function (_ref6) {
      var onClose = _ref6.onClose;
      return (0, _element.createElement)(_components.MenuGroup, null, (0, _element.createElement)(_components.MenuItem, {
        onClick: /*#__PURE__*/(0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee() {
          return _regenerator.default.wrap(function _callee$(_context) {
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
      }, (0, _i18n.__)('Go to block')));
    });
  }));
}
//# sourceMappingURL=block.js.map