"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockSettingsDropdown = BlockSettingsDropdown;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _asyncToGenerator2 = _interopRequireDefault(require("@babel/runtime/helpers/asyncToGenerator"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _blocks = require("@wordpress/blocks");

var _blockActions = _interopRequireDefault(require("../block-actions"));

var _blockModeToggle = _interopRequireDefault(require("./block-mode-toggle"));

var _blockHtmlConvertButton = _interopRequireDefault(require("./block-html-convert-button"));

var _blockSettingsMenuFirstItem = _interopRequireDefault(require("./block-settings-menu-first-item"));

var _blockSettingsMenuControls = _interopRequireDefault(require("../block-settings-menu-controls"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var POPOVER_PROPS = {
  className: 'block-editor-block-settings-menu__popover',
  position: 'bottom right',
  isAlternate: true
};

function BlockSettingsDropdown(_ref) {
  var clientIds = _ref.clientIds,
      __experimentalSelectBlock = _ref.__experimentalSelectBlock,
      children = _ref.children,
      props = (0, _objectWithoutProperties2.default)(_ref, ["clientIds", "__experimentalSelectBlock", "children"]);
  var blockClientIds = (0, _lodash.castArray)(clientIds);
  var count = blockClientIds.length;
  var firstBlockClientId = blockClientIds[0];
  var shortcuts = (0, _data.useSelect)(function (select) {
    var _select = select('core/keyboard-shortcuts'),
        getShortcutRepresentation = _select.getShortcutRepresentation;

    return {
      duplicate: getShortcutRepresentation('core/block-editor/duplicate'),
      remove: getShortcutRepresentation('core/block-editor/remove'),
      insertAfter: getShortcutRepresentation('core/block-editor/insert-after'),
      insertBefore: getShortcutRepresentation('core/block-editor/insert-before')
    };
  }, []);
  var updateSelection = (0, _element.useCallback)(__experimentalSelectBlock ? /*#__PURE__*/function () {
    var _ref2 = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee(clientIdsPromise) {
      var ids;
      return _regenerator.default.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.next = 2;
              return clientIdsPromise;

            case 2:
              ids = _context.sent;

              if (ids && ids[0]) {
                __experimentalSelectBlock(ids[0]);
              }

            case 4:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x) {
      return _ref2.apply(this, arguments);
    };
  }() : _lodash.noop, [__experimentalSelectBlock]);
  return (0, _element.createElement)(_blockActions.default, {
    clientIds: clientIds,
    __experimentalUpdateSelection: !__experimentalSelectBlock
  }, function (_ref3) {
    var canDuplicate = _ref3.canDuplicate,
        canInsertDefaultBlock = _ref3.canInsertDefaultBlock,
        isLocked = _ref3.isLocked,
        onDuplicate = _ref3.onDuplicate,
        onInsertAfter = _ref3.onInsertAfter,
        onInsertBefore = _ref3.onInsertBefore,
        onRemove = _ref3.onRemove,
        onCopy = _ref3.onCopy,
        onMoveTo = _ref3.onMoveTo,
        blocks = _ref3.blocks;
    return (0, _element.createElement)(_components.DropdownMenu, (0, _extends2.default)({
      icon: _icons.moreVertical,
      label: (0, _i18n.__)('More options'),
      className: "block-editor-block-settings-menu",
      popoverProps: POPOVER_PROPS,
      noIcons: true
    }, props), function (_ref4) {
      var onClose = _ref4.onClose;
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.MenuGroup, null, (0, _element.createElement)(_blockSettingsMenuFirstItem.default.Slot, {
        fillProps: {
          onClose: onClose
        }
      }), count === 1 && (0, _element.createElement)(_blockHtmlConvertButton.default, {
        clientId: firstBlockClientId
      }), (0, _element.createElement)(_components.ClipboardButton, {
        text: function text() {
          return (0, _blocks.serialize)(blocks);
        },
        role: "menuitem",
        className: "components-menu-item__button",
        onCopy: onCopy
      }, (0, _i18n.__)('Copy')), canDuplicate && (0, _element.createElement)(_components.MenuItem, {
        onClick: (0, _lodash.flow)(onClose, onDuplicate, updateSelection),
        shortcut: shortcuts.duplicate
      }, (0, _i18n.__)('Duplicate')), canInsertDefaultBlock && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.MenuItem, {
        onClick: (0, _lodash.flow)(onClose, onInsertBefore),
        shortcut: shortcuts.insertBefore
      }, (0, _i18n.__)('Insert Before')), (0, _element.createElement)(_components.MenuItem, {
        onClick: (0, _lodash.flow)(onClose, onInsertAfter),
        shortcut: shortcuts.insertAfter
      }, (0, _i18n.__)('Insert After'))), !isLocked && (0, _element.createElement)(_components.MenuItem, {
        onClick: (0, _lodash.flow)(onClose, onMoveTo)
      }, (0, _i18n.__)('Move To')), count === 1 && (0, _element.createElement)(_blockModeToggle.default, {
        clientId: firstBlockClientId,
        onToggle: onClose
      })), (0, _element.createElement)(_blockSettingsMenuControls.default.Slot, {
        fillProps: {
          onClose: onClose
        },
        clientIds: clientIds
      }), typeof children === 'function' ? children({
        onClose: onClose
      }) : _element.Children.map(function (child) {
        return (0, _element.cloneElement)(child, {
          onClose: onClose
        });
      }), (0, _element.createElement)(_components.MenuGroup, null, !isLocked && (0, _element.createElement)(_components.MenuItem, {
        onClick: (0, _lodash.flow)(onClose, onRemove, updateSelection),
        shortcut: shortcuts.remove
      }, (0, _i18n._n)('Remove block', 'Remove blocks', count))));
    });
  });
}

var _default = BlockSettingsDropdown;
exports.default = _default;
//# sourceMappingURL=block-settings-dropdown.js.map