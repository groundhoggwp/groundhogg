"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _keyboardShortcuts = require("@wordpress/keyboard-shortcuts");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function KeyboardShortcuts() {
  // Shortcuts Logic
  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
        getBlockOrder = _select.getBlockOrder,
        getBlockRootClientId = _select.getBlockRootClientId;

    var selectedClientIds = getSelectedBlockClientIds();

    var _selectedClientIds = (0, _slicedToArray2.default)(selectedClientIds, 1),
        firstClientId = _selectedClientIds[0];

    return {
      clientIds: selectedClientIds,
      rootBlocksClientIds: getBlockOrder(),
      rootClientId: getBlockRootClientId(firstClientId)
    };
  }, []),
      clientIds = _useSelect.clientIds,
      rootBlocksClientIds = _useSelect.rootBlocksClientIds,
      rootClientId = _useSelect.rootClientId;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      duplicateBlocks = _useDispatch.duplicateBlocks,
      removeBlocks = _useDispatch.removeBlocks,
      insertAfterBlock = _useDispatch.insertAfterBlock,
      insertBeforeBlock = _useDispatch.insertBeforeBlock,
      multiSelect = _useDispatch.multiSelect,
      clearSelectedBlock = _useDispatch.clearSelectedBlock,
      moveBlocksUp = _useDispatch.moveBlocksUp,
      moveBlocksDown = _useDispatch.moveBlocksDown; // Moves selected block/blocks up


  (0, _keyboardShortcuts.useShortcut)('core/block-editor/move-up', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    moveBlocksUp(clientIds, rootClientId);
  }, [clientIds, moveBlocksUp]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  }); // Moves selected block/blocks up

  (0, _keyboardShortcuts.useShortcut)('core/block-editor/move-down', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    moveBlocksDown(clientIds, rootClientId);
  }, [clientIds, moveBlocksDown]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  }); // Prevents bookmark all Tabs shortcut in Chrome when devtools are closed.
  // Prevents reposition Chrome devtools pane shortcut when devtools are open.

  (0, _keyboardShortcuts.useShortcut)('core/block-editor/duplicate', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    duplicateBlocks(clientIds);
  }, [clientIds, duplicateBlocks]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  }); // Does not clash with any known browser/native shortcuts, but preventDefault
  // is used to prevent any obscure unknown shortcuts from triggering.

  (0, _keyboardShortcuts.useShortcut)('core/block-editor/remove', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    removeBlocks(clientIds);
  }, [clientIds, removeBlocks]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  }); // Does not clash with any known browser/native shortcuts, but preventDefault
  // is used to prevent any obscure unknown shortcuts from triggering.

  (0, _keyboardShortcuts.useShortcut)('core/block-editor/insert-after', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    insertAfterBlock((0, _lodash.last)(clientIds));
  }, [clientIds, insertAfterBlock]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  }); // Prevent 'view recently closed tabs' in Opera using preventDefault.

  (0, _keyboardShortcuts.useShortcut)('core/block-editor/insert-before', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    insertBeforeBlock((0, _lodash.first)(clientIds));
  }, [clientIds, insertBeforeBlock]), {
    bindGlobal: true,
    isDisabled: clientIds.length === 0
  });
  (0, _keyboardShortcuts.useShortcut)('core/block-editor/delete-multi-selection', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    removeBlocks(clientIds);
  }, [clientIds, removeBlocks]), {
    isDisabled: clientIds.length < 2
  });
  (0, _keyboardShortcuts.useShortcut)('core/block-editor/select-all', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    multiSelect((0, _lodash.first)(rootBlocksClientIds), (0, _lodash.last)(rootBlocksClientIds));
  }, [rootBlocksClientIds, multiSelect]));
  (0, _keyboardShortcuts.useShortcut)('core/block-editor/unselect', (0, _element.useCallback)(function (event) {
    event.preventDefault();
    clearSelectedBlock();
    event.target.ownerDocument.defaultView.getSelection().removeAllRanges();
  }, [clientIds, clearSelectedBlock]), {
    isDisabled: clientIds.length < 2
  });
  return null;
}

function KeyboardShortcutsRegister() {
  // Registering the shortcuts
  var _useDispatch2 = (0, _data.useDispatch)('core/keyboard-shortcuts'),
      registerShortcut = _useDispatch2.registerShortcut;

  (0, _element.useEffect)(function () {
    registerShortcut({
      name: 'core/block-editor/duplicate',
      category: 'block',
      description: (0, _i18n.__)('Duplicate the selected block(s).'),
      keyCombination: {
        modifier: 'primaryShift',
        character: 'd'
      }
    });
    registerShortcut({
      name: 'core/block-editor/remove',
      category: 'block',
      description: (0, _i18n.__)('Remove the selected block(s).'),
      keyCombination: {
        modifier: 'access',
        character: 'z'
      }
    });
    registerShortcut({
      name: 'core/block-editor/insert-before',
      category: 'block',
      description: (0, _i18n.__)('Insert a new block before the selected block(s).'),
      keyCombination: {
        modifier: 'primaryAlt',
        character: 't'
      }
    });
    registerShortcut({
      name: 'core/block-editor/insert-after',
      category: 'block',
      description: (0, _i18n.__)('Insert a new block after the selected block(s).'),
      keyCombination: {
        modifier: 'primaryAlt',
        character: 'y'
      }
    });
    registerShortcut({
      name: 'core/block-editor/delete-multi-selection',
      category: 'block',
      description: (0, _i18n.__)('Remove multiple selected blocks.'),
      keyCombination: {
        character: 'del'
      },
      aliases: [{
        character: 'backspace'
      }]
    });
    registerShortcut({
      name: 'core/block-editor/select-all',
      category: 'selection',
      description: (0, _i18n.__)('Select all text when typing. Press again to select all blocks.'),
      keyCombination: {
        modifier: 'primary',
        character: 'a'
      }
    });
    registerShortcut({
      name: 'core/block-editor/unselect',
      category: 'selection',
      description: (0, _i18n.__)('Clear selection.'),
      keyCombination: {
        character: 'escape'
      }
    });
    registerShortcut({
      name: 'core/block-editor/focus-toolbar',
      category: 'global',
      description: (0, _i18n.__)('Navigate to the nearest toolbar.'),
      keyCombination: {
        modifier: 'alt',
        character: 'F10'
      }
    });
    registerShortcut({
      name: 'core/block-editor/move-up',
      category: 'block',
      description: (0, _i18n.__)('Move the selected block(s) up.'),
      keyCombination: {
        modifier: 'secondary',
        character: 't'
      }
    });
    registerShortcut({
      name: 'core/block-editor/move-down',
      category: 'block',
      description: (0, _i18n.__)('Move the selected block(s) down.'),
      keyCombination: {
        modifier: 'secondary',
        character: 'y'
      }
    });
  }, [registerShortcut]);
  return null;
}

KeyboardShortcuts.Register = KeyboardShortcutsRegister;
var _default = KeyboardShortcuts;
exports.default = _default;
//# sourceMappingURL=index.js.map