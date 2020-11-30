import _extends from "@babel/runtime/helpers/esm/extends";
import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { castArray, flow, noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, _n } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem, ClipboardButton } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { moreVertical } from '@wordpress/icons';
import { Children, cloneElement, useCallback } from '@wordpress/element';
import { serialize } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import BlockActions from '../block-actions';
import BlockModeToggle from './block-mode-toggle';
import BlockHTMLConvertButton from './block-html-convert-button';
import __experimentalBlockSettingsMenuFirstItem from './block-settings-menu-first-item';
import BlockSettingsMenuControls from '../block-settings-menu-controls';
var POPOVER_PROPS = {
  className: 'block-editor-block-settings-menu__popover',
  position: 'bottom right',
  isAlternate: true
};
export function BlockSettingsDropdown(_ref) {
  var clientIds = _ref.clientIds,
      __experimentalSelectBlock = _ref.__experimentalSelectBlock,
      children = _ref.children,
      props = _objectWithoutProperties(_ref, ["clientIds", "__experimentalSelectBlock", "children"]);

  var blockClientIds = castArray(clientIds);
  var count = blockClientIds.length;
  var firstBlockClientId = blockClientIds[0];
  var shortcuts = useSelect(function (select) {
    var _select = select('core/keyboard-shortcuts'),
        getShortcutRepresentation = _select.getShortcutRepresentation;

    return {
      duplicate: getShortcutRepresentation('core/block-editor/duplicate'),
      remove: getShortcutRepresentation('core/block-editor/remove'),
      insertAfter: getShortcutRepresentation('core/block-editor/insert-after'),
      insertBefore: getShortcutRepresentation('core/block-editor/insert-before')
    };
  }, []);
  var updateSelection = useCallback(__experimentalSelectBlock ? /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(clientIdsPromise) {
      var ids;
      return _regeneratorRuntime.wrap(function _callee$(_context) {
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
  }() : noop, [__experimentalSelectBlock]);
  return createElement(BlockActions, {
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
    return createElement(DropdownMenu, _extends({
      icon: moreVertical,
      label: __('More options'),
      className: "block-editor-block-settings-menu",
      popoverProps: POPOVER_PROPS,
      noIcons: true
    }, props), function (_ref4) {
      var onClose = _ref4.onClose;
      return createElement(Fragment, null, createElement(MenuGroup, null, createElement(__experimentalBlockSettingsMenuFirstItem.Slot, {
        fillProps: {
          onClose: onClose
        }
      }), count === 1 && createElement(BlockHTMLConvertButton, {
        clientId: firstBlockClientId
      }), createElement(ClipboardButton, {
        text: function text() {
          return serialize(blocks);
        },
        role: "menuitem",
        className: "components-menu-item__button",
        onCopy: onCopy
      }, __('Copy')), canDuplicate && createElement(MenuItem, {
        onClick: flow(onClose, onDuplicate, updateSelection),
        shortcut: shortcuts.duplicate
      }, __('Duplicate')), canInsertDefaultBlock && createElement(Fragment, null, createElement(MenuItem, {
        onClick: flow(onClose, onInsertBefore),
        shortcut: shortcuts.insertBefore
      }, __('Insert Before')), createElement(MenuItem, {
        onClick: flow(onClose, onInsertAfter),
        shortcut: shortcuts.insertAfter
      }, __('Insert After'))), !isLocked && createElement(MenuItem, {
        onClick: flow(onClose, onMoveTo)
      }, __('Move To')), count === 1 && createElement(BlockModeToggle, {
        clientId: firstBlockClientId,
        onToggle: onClose
      })), createElement(BlockSettingsMenuControls.Slot, {
        fillProps: {
          onClose: onClose
        },
        clientIds: clientIds
      }), typeof children === 'function' ? children({
        onClose: onClose
      }) : Children.map(function (child) {
        return cloneElement(child, {
          onClose: onClose
        });
      }), createElement(MenuGroup, null, !isLocked && createElement(MenuItem, {
        onClick: flow(onClose, onRemove, updateSelection),
        shortcut: shortcuts.remove
      }, _n('Remove block', 'Remove blocks', count))));
    });
  });
}
export default BlockSettingsDropdown;
//# sourceMappingURL=block-settings-dropdown.js.map