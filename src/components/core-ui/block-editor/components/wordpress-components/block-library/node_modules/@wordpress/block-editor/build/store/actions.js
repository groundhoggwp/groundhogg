"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.resetBlocks = resetBlocks;
exports.resetSelection = resetSelection;
exports.receiveBlocks = receiveBlocks;
exports.updateBlockAttributes = updateBlockAttributes;
exports.updateBlock = updateBlock;
exports.selectBlock = selectBlock;
exports.selectPreviousBlock = selectPreviousBlock;
exports.selectNextBlock = selectNextBlock;
exports.startMultiSelect = startMultiSelect;
exports.stopMultiSelect = stopMultiSelect;
exports.multiSelect = multiSelect;
exports.clearSelectedBlock = clearSelectedBlock;
exports.toggleSelection = toggleSelection;
exports.replaceBlocks = replaceBlocks;
exports.replaceBlock = replaceBlock;
exports.moveBlocksToPosition = moveBlocksToPosition;
exports.moveBlockToPosition = moveBlockToPosition;
exports.insertBlock = insertBlock;
exports.insertBlocks = insertBlocks;
exports.showInsertionPoint = showInsertionPoint;
exports.hideInsertionPoint = hideInsertionPoint;
exports.setTemplateValidity = setTemplateValidity;
exports.synchronizeTemplate = synchronizeTemplate;
exports.mergeBlocks = mergeBlocks;
exports.removeBlocks = removeBlocks;
exports.removeBlock = removeBlock;
exports.replaceInnerBlocks = replaceInnerBlocks;
exports.toggleBlockMode = toggleBlockMode;
exports.startTyping = startTyping;
exports.stopTyping = stopTyping;
exports.startDraggingBlocks = startDraggingBlocks;
exports.stopDraggingBlocks = stopDraggingBlocks;
exports.enterFormattedText = enterFormattedText;
exports.exitFormattedText = exitFormattedText;
exports.selectionChange = selectionChange;
exports.insertDefaultBlock = insertDefaultBlock;
exports.updateBlockListSettings = updateBlockListSettings;
exports.updateSettings = updateSettings;
exports.__unstableSaveReusableBlock = __unstableSaveReusableBlock;
exports.__unstableMarkLastChangeAsPersistent = __unstableMarkLastChangeAsPersistent;
exports.__unstableMarkNextChangeAsNotPersistent = __unstableMarkNextChangeAsNotPersistent;
exports.__unstableMarkAutomaticChange = __unstableMarkAutomaticChange;
exports.setNavigationMode = setNavigationMode;
exports.setBlockMovingClientId = setBlockMovingClientId;
exports.duplicateBlocks = duplicateBlocks;
exports.insertBeforeBlock = insertBeforeBlock;
exports.insertAfterBlock = insertAfterBlock;
exports.toggleBlockHighlight = toggleBlockHighlight;
exports.flashBlock = flashBlock;
exports.setHasControlledInnerBlocks = setHasControlledInnerBlocks;
exports.moveBlocksUp = exports.moveBlocksDown = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _lodash = require("lodash");

var _blocks = require("@wordpress/blocks");

var _a11y = require("@wordpress/a11y");

var _i18n = require("@wordpress/i18n");

var _controls = require("./controls");

function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _marked = /*#__PURE__*/_regenerator.default.mark(ensureDefaultBlock),
    _marked2 = /*#__PURE__*/_regenerator.default.mark(selectPreviousBlock),
    _marked3 = /*#__PURE__*/_regenerator.default.mark(selectNextBlock),
    _marked4 = /*#__PURE__*/_regenerator.default.mark(replaceBlocks),
    _marked5 = /*#__PURE__*/_regenerator.default.mark(moveBlocksToPosition),
    _marked6 = /*#__PURE__*/_regenerator.default.mark(moveBlockToPosition),
    _marked7 = /*#__PURE__*/_regenerator.default.mark(insertBlocks),
    _marked8 = /*#__PURE__*/_regenerator.default.mark(removeBlocks),
    _marked9 = /*#__PURE__*/_regenerator.default.mark(setNavigationMode),
    _marked10 = /*#__PURE__*/_regenerator.default.mark(setBlockMovingClientId),
    _marked11 = /*#__PURE__*/_regenerator.default.mark(duplicateBlocks),
    _marked12 = /*#__PURE__*/_regenerator.default.mark(insertBeforeBlock),
    _marked13 = /*#__PURE__*/_regenerator.default.mark(insertAfterBlock),
    _marked14 = /*#__PURE__*/_regenerator.default.mark(flashBlock);

/**
 * Generator which will yield a default block insert action if there
 * are no other blocks at the root of the editor. This generator should be used
 * in actions which may result in no blocks remaining in the editor (removal,
 * replacement, etc).
 */
function ensureDefaultBlock() {
  var count;
  return _regenerator.default.wrap(function ensureDefaultBlock$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return (0, _controls.select)('core/block-editor', 'getBlockCount');

        case 2:
          count = _context.sent;

          if (!(count === 0)) {
            _context.next = 7;
            break;
          }

          _context.next = 6;
          return insertDefaultBlock();

        case 6:
          return _context.abrupt("return", _context.sent);

        case 7:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
}
/**
 * Returns an action object used in signalling that blocks state should be
 * reset to the specified array of blocks, taking precedence over any other
 * content reflected as an edit in state.
 *
 * @param {Array} blocks Array of blocks.
 *
 * @return {Object} Action object.
 */


function resetBlocks(blocks) {
  return {
    type: 'RESET_BLOCKS',
    blocks: blocks
  };
}
/**
 * A block selection object.
 *
 * @typedef {Object} WPBlockSelection
 *
 * @property {string} clientId     A block client ID.
 * @property {string} attributeKey A block attribute key.
 * @property {number} offset       An attribute value offset, based on the rich
 *                                 text value. See `wp.richText.create`.
 */

/**
 * Returns an action object used in signalling that selection state should be
 * reset to the specified selection.
 *
 * @param {WPBlockSelection} selectionStart The selection start.
 * @param {WPBlockSelection} selectionEnd   The selection end.
 *
 * @return {Object} Action object.
 */


function resetSelection(selectionStart, selectionEnd) {
  return {
    type: 'RESET_SELECTION',
    selectionStart: selectionStart,
    selectionEnd: selectionEnd
  };
}
/**
 * Returns an action object used in signalling that blocks have been received.
 * Unlike resetBlocks, these should be appended to the existing known set, not
 * replacing.
 *
 * @param {Object[]} blocks Array of block objects.
 *
 * @return {Object} Action object.
 */


function receiveBlocks(blocks) {
  return {
    type: 'RECEIVE_BLOCKS',
    blocks: blocks
  };
}
/**
 * Returns an action object used in signalling that the multiple blocks'
 * attributes with the specified client IDs have been updated.
 *
 * @param {string|string[]} clientIds  Block client IDs.
 * @param {Object}          attributes Block attributes to be merged.
 *
 * @return {Object} Action object.
 */


function updateBlockAttributes(clientIds, attributes) {
  return {
    type: 'UPDATE_BLOCK_ATTRIBUTES',
    clientIds: (0, _lodash.castArray)(clientIds),
    attributes: attributes
  };
}
/**
 * Returns an action object used in signalling that the block with the
 * specified client ID has been updated.
 *
 * @param {string} clientId Block client ID.
 * @param {Object} updates  Block attributes to be merged.
 *
 * @return {Object} Action object.
 */


function updateBlock(clientId, updates) {
  return {
    type: 'UPDATE_BLOCK',
    clientId: clientId,
    updates: updates
  };
}
/**
 * Returns an action object used in signalling that the block with the
 * specified client ID has been selected, optionally accepting a position
 * value reflecting its selection directionality. An initialPosition of -1
 * reflects a reverse selection.
 *
 * @param {string}  clientId        Block client ID.
 * @param {?number} initialPosition Optional initial position. Pass as -1 to
 *                                  reflect reverse selection.
 *
 * @return {Object} Action object.
 */


function selectBlock(clientId) {
  var initialPosition = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  return {
    type: 'SELECT_BLOCK',
    initialPosition: initialPosition,
    clientId: clientId
  };
}
/**
 * Yields action objects used in signalling that the block preceding the given
 * clientId should be selected.
 *
 * @param {string} clientId Block client ID.
 */


function selectPreviousBlock(clientId) {
  var previousBlockClientId;
  return _regenerator.default.wrap(function selectPreviousBlock$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return (0, _controls.select)('core/block-editor', 'getPreviousBlockClientId', clientId);

        case 2:
          previousBlockClientId = _context2.sent;

          if (!previousBlockClientId) {
            _context2.next = 7;
            break;
          }

          _context2.next = 6;
          return selectBlock(previousBlockClientId, -1);

        case 6:
          return _context2.abrupt("return", [previousBlockClientId]);

        case 7:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2);
}
/**
 * Yields action objects used in signalling that the block following the given
 * clientId should be selected.
 *
 * @param {string} clientId Block client ID.
 */


function selectNextBlock(clientId) {
  var nextBlockClientId;
  return _regenerator.default.wrap(function selectNextBlock$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return (0, _controls.select)('core/block-editor', 'getNextBlockClientId', clientId);

        case 2:
          nextBlockClientId = _context3.sent;

          if (!nextBlockClientId) {
            _context3.next = 7;
            break;
          }

          _context3.next = 6;
          return selectBlock(nextBlockClientId);

        case 6:
          return _context3.abrupt("return", [nextBlockClientId]);

        case 7:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3);
}
/**
 * Returns an action object used in signalling that a block multi-selection has started.
 *
 * @return {Object} Action object.
 */


function startMultiSelect() {
  return {
    type: 'START_MULTI_SELECT'
  };
}
/**
 * Returns an action object used in signalling that block multi-selection stopped.
 *
 * @return {Object} Action object.
 */


function stopMultiSelect() {
  return {
    type: 'STOP_MULTI_SELECT'
  };
}
/**
 * Returns an action object used in signalling that block multi-selection changed.
 *
 * @param {string} start First block of the multi selection.
 * @param {string} end   Last block of the multiselection.
 *
 * @return {Object} Action object.
 */


function multiSelect(start, end) {
  return {
    type: 'MULTI_SELECT',
    start: start,
    end: end
  };
}
/**
 * Returns an action object used in signalling that the block selection is cleared.
 *
 * @return {Object} Action object.
 */


function clearSelectedBlock() {
  return {
    type: 'CLEAR_SELECTED_BLOCK'
  };
}
/**
 * Returns an action object that enables or disables block selection.
 *
 * @param {boolean} [isSelectionEnabled=true] Whether block selection should
 *                                            be enabled.
 *
 * @return {Object} Action object.
 */


function toggleSelection() {
  var isSelectionEnabled = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
  return {
    type: 'TOGGLE_SELECTION',
    isSelectionEnabled: isSelectionEnabled
  };
}

function getBlocksWithDefaultStylesApplied(blocks, blockEditorSettings) {
  var preferredStyleVariations = (0, _lodash.get)(blockEditorSettings, ['__experimentalPreferredStyleVariations', 'value'], {});
  return blocks.map(function (block) {
    var blockName = block.name;

    if (!(0, _blocks.hasBlockSupport)(blockName, 'defaultStylePicker', true)) {
      return block;
    }

    if (!preferredStyleVariations[blockName]) {
      return block;
    }

    var className = (0, _lodash.get)(block, ['attributes', 'className']);

    if (className === null || className === void 0 ? void 0 : className.includes('is-style-')) {
      return block;
    }

    var _block$attributes = block.attributes,
        attributes = _block$attributes === void 0 ? {} : _block$attributes;
    var blockStyle = preferredStyleVariations[blockName];
    return _objectSpread(_objectSpread({}, block), {}, {
      attributes: _objectSpread(_objectSpread({}, attributes), {}, {
        className: "".concat(className || '', " is-style-").concat(blockStyle).trim()
      })
    });
  });
}
/**
 * Returns an action object signalling that a blocks should be replaced with
 * one or more replacement blocks.
 *
 * @param {(string|string[])} clientIds       Block client ID(s) to replace.
 * @param {(Object|Object[])} blocks          Replacement block(s).
 * @param {number}            indexToSelect   Index of replacement block to select.
 * @param {number}            initialPosition Index of caret after in the selected block after the operation.
 * @param {?Object}           meta            Optional Meta values to be passed to the action object.
 *
 * @yield {Object} Action object.
 */


function replaceBlocks(clientIds, blocks, indexToSelect, initialPosition, meta) {
  var rootClientId, index, block, canInsertBlock;
  return _regenerator.default.wrap(function replaceBlocks$(_context4) {
    while (1) {
      switch (_context4.prev = _context4.next) {
        case 0:
          clientIds = (0, _lodash.castArray)(clientIds);
          _context4.t0 = getBlocksWithDefaultStylesApplied;
          _context4.t1 = (0, _lodash.castArray)(blocks);
          _context4.next = 5;
          return (0, _controls.select)('core/block-editor', 'getSettings');

        case 5:
          _context4.t2 = _context4.sent;
          blocks = (0, _context4.t0)(_context4.t1, _context4.t2);
          _context4.next = 9;
          return (0, _controls.select)('core/block-editor', 'getBlockRootClientId', (0, _lodash.first)(clientIds));

        case 9:
          rootClientId = _context4.sent;
          index = 0;

        case 11:
          if (!(index < blocks.length)) {
            _context4.next = 21;
            break;
          }

          block = blocks[index];
          _context4.next = 15;
          return (0, _controls.select)('core/block-editor', 'canInsertBlockType', block.name, rootClientId);

        case 15:
          canInsertBlock = _context4.sent;

          if (canInsertBlock) {
            _context4.next = 18;
            break;
          }

          return _context4.abrupt("return");

        case 18:
          index++;
          _context4.next = 11;
          break;

        case 21:
          _context4.next = 23;
          return {
            type: 'REPLACE_BLOCKS',
            clientIds: clientIds,
            blocks: blocks,
            time: Date.now(),
            indexToSelect: indexToSelect,
            initialPosition: initialPosition,
            meta: meta
          };

        case 23:
          return _context4.delegateYield(ensureDefaultBlock(), "t3", 24);

        case 24:
        case "end":
          return _context4.stop();
      }
    }
  }, _marked4);
}
/**
 * Returns an action object signalling that a single block should be replaced
 * with one or more replacement blocks.
 *
 * @param {(string|string[])} clientId Block client ID to replace.
 * @param {(Object|Object[])} block    Replacement block(s).
 *
 * @return {Object} Action object.
 */


function replaceBlock(clientId, block) {
  return replaceBlocks(clientId, block);
}
/**
 * Higher-order action creator which, given the action type to dispatch creates
 * an action creator for managing block movement.
 *
 * @param {string} type Action type to dispatch.
 *
 * @return {Function} Action creator.
 */


function createOnMove(type) {
  return function (clientIds, rootClientId) {
    return {
      clientIds: (0, _lodash.castArray)(clientIds),
      type: type,
      rootClientId: rootClientId
    };
  };
}

var moveBlocksDown = createOnMove('MOVE_BLOCKS_DOWN');
exports.moveBlocksDown = moveBlocksDown;
var moveBlocksUp = createOnMove('MOVE_BLOCKS_UP');
/**
 * Returns an action object signalling that the given blocks should be moved to
 * a new position.
 *
 * @param  {?string} clientIds        The client IDs of the blocks.
 * @param  {?string} fromRootClientId Root client ID source.
 * @param  {?string} toRootClientId   Root client ID destination.
 * @param  {number}  index            The index to move the blocks to.
 *
 * @yield {Object} Action object.
 */

exports.moveBlocksUp = moveBlocksUp;

function moveBlocksToPosition(clientIds) {
  var fromRootClientId,
      toRootClientId,
      index,
      templateLock,
      action,
      canInsertBlocks,
      _args5 = arguments;
  return _regenerator.default.wrap(function moveBlocksToPosition$(_context5) {
    while (1) {
      switch (_context5.prev = _context5.next) {
        case 0:
          fromRootClientId = _args5.length > 1 && _args5[1] !== undefined ? _args5[1] : '';
          toRootClientId = _args5.length > 2 && _args5[2] !== undefined ? _args5[2] : '';
          index = _args5.length > 3 ? _args5[3] : undefined;
          _context5.next = 5;
          return (0, _controls.select)('core/block-editor', 'getTemplateLock', fromRootClientId);

        case 5:
          templateLock = _context5.sent;

          if (!(templateLock === 'all')) {
            _context5.next = 8;
            break;
          }

          return _context5.abrupt("return");

        case 8:
          action = {
            type: 'MOVE_BLOCKS_TO_POSITION',
            fromRootClientId: fromRootClientId,
            toRootClientId: toRootClientId,
            clientIds: clientIds,
            index: index
          }; // If moving inside the same root block the move is always possible.

          if (!(fromRootClientId === toRootClientId)) {
            _context5.next = 13;
            break;
          }

          _context5.next = 12;
          return action;

        case 12:
          return _context5.abrupt("return");

        case 13:
          if (!(templateLock === 'insert')) {
            _context5.next = 15;
            break;
          }

          return _context5.abrupt("return");

        case 15:
          _context5.next = 17;
          return (0, _controls.select)('core/block-editor', 'canInsertBlocks', clientIds, toRootClientId);

        case 17:
          canInsertBlocks = _context5.sent;

          if (!canInsertBlocks) {
            _context5.next = 21;
            break;
          }

          _context5.next = 21;
          return action;

        case 21:
        case "end":
          return _context5.stop();
      }
    }
  }, _marked5);
}
/**
 * Returns an action object signalling that the given block should be moved to a
 * new position.
 *
 * @param  {?string} clientId         The client ID of the block.
 * @param  {?string} fromRootClientId Root client ID source.
 * @param  {?string} toRootClientId   Root client ID destination.
 * @param  {number}  index            The index to move the block to.
 *
 * @yield {Object} Action object.
 */


function moveBlockToPosition(clientId) {
  var fromRootClientId,
      toRootClientId,
      index,
      _args6 = arguments;
  return _regenerator.default.wrap(function moveBlockToPosition$(_context6) {
    while (1) {
      switch (_context6.prev = _context6.next) {
        case 0:
          fromRootClientId = _args6.length > 1 && _args6[1] !== undefined ? _args6[1] : '';
          toRootClientId = _args6.length > 2 && _args6[2] !== undefined ? _args6[2] : '';
          index = _args6.length > 3 ? _args6[3] : undefined;
          _context6.next = 5;
          return moveBlocksToPosition([clientId], fromRootClientId, toRootClientId, index);

        case 5:
        case "end":
          return _context6.stop();
      }
    }
  }, _marked6);
}
/**
 * Returns an action object used in signalling that a single block should be
 * inserted, optionally at a specific index respective a root block list.
 *
 * @param {Object}  block            Block object to insert.
 * @param {?number} index            Index at which block should be inserted.
 * @param {?string} rootClientId     Optional root client ID of block list on which to insert.
 * @param {?boolean} updateSelection If true block selection will be updated. If false, block selection will not change. Defaults to true.
 *
 * @return {Object} Action object.
 */


function insertBlock(block, index, rootClientId) {
  var updateSelection = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
  return insertBlocks([block], index, rootClientId, updateSelection);
}
/**
 * Returns an action object used in signalling that an array of blocks should
 * be inserted, optionally at a specific index respective a root block list.
 *
 * @param {Object[]}   blocks          Block objects to insert.
 * @param {?number}    index           Index at which block should be inserted.
 * @param {?string}    rootClientId    Optional root client ID of block list on which to insert.
 * @param {?boolean}   updateSelection If true block selection will be updated.  If false, block selection will not change. Defaults to true.
 * @param {?Object}  meta             Optional Meta values to be passed to the action object.
 *
 *  @return {Object} Action object.
 */


function insertBlocks(blocks, index, rootClientId) {
  var updateSelection,
      meta,
      allowedBlocks,
      _iterator,
      _step,
      block,
      isValid,
      _args7 = arguments;

  return _regenerator.default.wrap(function insertBlocks$(_context7) {
    while (1) {
      switch (_context7.prev = _context7.next) {
        case 0:
          updateSelection = _args7.length > 3 && _args7[3] !== undefined ? _args7[3] : true;
          meta = _args7.length > 4 ? _args7[4] : undefined;
          _context7.t0 = getBlocksWithDefaultStylesApplied;
          _context7.t1 = (0, _lodash.castArray)(blocks);
          _context7.next = 6;
          return (0, _controls.select)('core/block-editor', 'getSettings');

        case 6:
          _context7.t2 = _context7.sent;
          blocks = (0, _context7.t0)(_context7.t1, _context7.t2);
          allowedBlocks = [];
          _iterator = _createForOfIteratorHelper(blocks);
          _context7.prev = 10;

          _iterator.s();

        case 12:
          if ((_step = _iterator.n()).done) {
            _context7.next = 20;
            break;
          }

          block = _step.value;
          _context7.next = 16;
          return (0, _controls.select)('core/block-editor', 'canInsertBlockType', block.name, rootClientId);

        case 16:
          isValid = _context7.sent;

          if (isValid) {
            allowedBlocks.push(block);
          }

        case 18:
          _context7.next = 12;
          break;

        case 20:
          _context7.next = 25;
          break;

        case 22:
          _context7.prev = 22;
          _context7.t3 = _context7["catch"](10);

          _iterator.e(_context7.t3);

        case 25:
          _context7.prev = 25;

          _iterator.f();

          return _context7.finish(25);

        case 28:
          if (!allowedBlocks.length) {
            _context7.next = 30;
            break;
          }

          return _context7.abrupt("return", {
            type: 'INSERT_BLOCKS',
            blocks: allowedBlocks,
            index: index,
            rootClientId: rootClientId,
            time: Date.now(),
            updateSelection: updateSelection,
            meta: meta
          });

        case 30:
        case "end":
          return _context7.stop();
      }
    }
  }, _marked7, null, [[10, 22, 25, 28]]);
}
/**
 * Returns an action object used in signalling that the insertion point should
 * be shown.
 *
 * @param {?string} rootClientId Optional root client ID of block list on
 *                               which to insert.
 * @param {?number} index        Index at which block should be inserted.
 *
 * @return {Object} Action object.
 */


function showInsertionPoint(rootClientId, index) {
  return {
    type: 'SHOW_INSERTION_POINT',
    rootClientId: rootClientId,
    index: index
  };
}
/**
 * Returns an action object hiding the insertion point.
 *
 * @return {Object} Action object.
 */


function hideInsertionPoint() {
  return {
    type: 'HIDE_INSERTION_POINT'
  };
}
/**
 * Returns an action object resetting the template validity.
 *
 * @param {boolean}  isValid  template validity flag.
 *
 * @return {Object} Action object.
 */


function setTemplateValidity(isValid) {
  return {
    type: 'SET_TEMPLATE_VALIDITY',
    isValid: isValid
  };
}
/**
 * Returns an action object synchronize the template with the list of blocks
 *
 * @return {Object} Action object.
 */


function synchronizeTemplate() {
  return {
    type: 'SYNCHRONIZE_TEMPLATE'
  };
}
/**
 * Returns an action object used in signalling that two blocks should be merged
 *
 * @param {string} firstBlockClientId  Client ID of the first block to merge.
 * @param {string} secondBlockClientId Client ID of the second block to merge.
 *
 * @return {Object} Action object.
 */


function mergeBlocks(firstBlockClientId, secondBlockClientId) {
  return {
    type: 'MERGE_BLOCKS',
    blocks: [firstBlockClientId, secondBlockClientId]
  };
}
/**
 * Yields action objects used in signalling that the blocks corresponding to
 * the set of specified client IDs are to be removed.
 *
 * @param {string|string[]} clientIds      Client IDs of blocks to remove.
 * @param {boolean}         selectPrevious True if the previous block should be
 *                                         selected when a block is removed.
 */


function removeBlocks(clientIds) {
  var selectPrevious,
      rootClientId,
      isLocked,
      previousBlockId,
      defaultBlockId,
      _args8 = arguments;
  return _regenerator.default.wrap(function removeBlocks$(_context8) {
    while (1) {
      switch (_context8.prev = _context8.next) {
        case 0:
          selectPrevious = _args8.length > 1 && _args8[1] !== undefined ? _args8[1] : true;

          if (!(!clientIds || !clientIds.length)) {
            _context8.next = 3;
            break;
          }

          return _context8.abrupt("return");

        case 3:
          clientIds = (0, _lodash.castArray)(clientIds);
          _context8.next = 6;
          return (0, _controls.select)('core/block-editor', 'getBlockRootClientId', clientIds[0]);

        case 6:
          rootClientId = _context8.sent;
          _context8.next = 9;
          return (0, _controls.select)('core/block-editor', 'getTemplateLock', rootClientId);

        case 9:
          isLocked = _context8.sent;

          if (!isLocked) {
            _context8.next = 12;
            break;
          }

          return _context8.abrupt("return");

        case 12:
          if (!selectPrevious) {
            _context8.next = 18;
            break;
          }

          _context8.next = 15;
          return selectPreviousBlock(clientIds[0]);

        case 15:
          previousBlockId = _context8.sent;
          _context8.next = 21;
          break;

        case 18:
          _context8.next = 20;
          return (0, _controls.select)('core/block-editor', 'getPreviousBlockClientId', clientIds[0]);

        case 20:
          previousBlockId = _context8.sent;

        case 21:
          _context8.next = 23;
          return {
            type: 'REMOVE_BLOCKS',
            clientIds: clientIds
          };

        case 23:
          return _context8.delegateYield(ensureDefaultBlock(), "t0", 24);

        case 24:
          defaultBlockId = _context8.t0;
          return _context8.abrupt("return", [previousBlockId || defaultBlockId]);

        case 26:
        case "end":
          return _context8.stop();
      }
    }
  }, _marked8);
}
/**
 * Returns an action object used in signalling that the block with the
 * specified client ID is to be removed.
 *
 * @param {string}  clientId       Client ID of block to remove.
 * @param {boolean} selectPrevious True if the previous block should be
 *                                 selected when a block is removed.
 *
 * @return {Object} Action object.
 */


function removeBlock(clientId, selectPrevious) {
  return removeBlocks([clientId], selectPrevious);
}
/**
 * Returns an action object used in signalling that the inner blocks with the
 * specified client ID should be replaced.
 *
 * @param {string}   rootClientId    Client ID of the block whose InnerBlocks will re replaced.
 * @param {Object[]} blocks          Block objects to insert as new InnerBlocks
 * @param {?boolean} updateSelection If true block selection will be updated. If false, block selection will not change. Defaults to true.
 *
 * @return {Object} Action object.
 */


function replaceInnerBlocks(rootClientId, blocks) {
  var updateSelection = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
  return {
    type: 'REPLACE_INNER_BLOCKS',
    rootClientId: rootClientId,
    blocks: blocks,
    updateSelection: updateSelection,
    time: Date.now()
  };
}
/**
 * Returns an action object used to toggle the block editing mode between
 * visual and HTML modes.
 *
 * @param {string} clientId Block client ID.
 *
 * @return {Object} Action object.
 */


function toggleBlockMode(clientId) {
  return {
    type: 'TOGGLE_BLOCK_MODE',
    clientId: clientId
  };
}
/**
 * Returns an action object used in signalling that the user has begun to type.
 *
 * @return {Object} Action object.
 */


function startTyping() {
  return {
    type: 'START_TYPING'
  };
}
/**
 * Returns an action object used in signalling that the user has stopped typing.
 *
 * @return {Object} Action object.
 */


function stopTyping() {
  return {
    type: 'STOP_TYPING'
  };
}
/**
 * Returns an action object used in signalling that the user has begun to drag blocks.
 *
 * @param {string[]} clientIds An array of client ids being dragged
 *
 * @return {Object} Action object.
 */


function startDraggingBlocks() {
  var clientIds = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  return {
    type: 'START_DRAGGING_BLOCKS',
    clientIds: clientIds
  };
}
/**
 * Returns an action object used in signalling that the user has stopped dragging blocks.
 *
 * @return {Object} Action object.
 */


function stopDraggingBlocks() {
  return {
    type: 'STOP_DRAGGING_BLOCKS'
  };
}
/**
 * Returns an action object used in signalling that the caret has entered formatted text.
 *
 * @return {Object} Action object.
 */


function enterFormattedText() {
  return {
    type: 'ENTER_FORMATTED_TEXT'
  };
}
/**
 * Returns an action object used in signalling that the user caret has exited formatted text.
 *
 * @return {Object} Action object.
 */


function exitFormattedText() {
  return {
    type: 'EXIT_FORMATTED_TEXT'
  };
}
/**
 * Returns an action object used in signalling that the user caret has changed
 * position.
 *
 * @param {string} clientId     The selected block client ID.
 * @param {string} attributeKey The selected block attribute key.
 * @param {number} startOffset  The start offset.
 * @param {number} endOffset    The end offset.
 *
 * @return {Object} Action object.
 */


function selectionChange(clientId, attributeKey, startOffset, endOffset) {
  return {
    type: 'SELECTION_CHANGE',
    clientId: clientId,
    attributeKey: attributeKey,
    startOffset: startOffset,
    endOffset: endOffset
  };
}
/**
 * Returns an action object used in signalling that a new block of the default
 * type should be added to the block list.
 *
 * @param {?Object} attributes   Optional attributes of the block to assign.
 * @param {?string} rootClientId Optional root client ID of block list on which
 *                               to append.
 * @param {?number} index        Optional index where to insert the default block
 *
 * @return {Object} Action object
 */


function insertDefaultBlock(attributes, rootClientId, index) {
  // Abort if there is no default block type (if it has been unregistered).
  var defaultBlockName = (0, _blocks.getDefaultBlockName)();

  if (!defaultBlockName) {
    return;
  }

  var block = (0, _blocks.createBlock)(defaultBlockName, attributes);
  return insertBlock(block, index, rootClientId);
}
/**
 * Returns an action object that changes the nested settings of a given block.
 *
 * @param {string} clientId Client ID of the block whose nested setting are
 *                          being received.
 * @param {Object} settings Object with the new settings for the nested block.
 *
 * @return {Object} Action object
 */


function updateBlockListSettings(clientId, settings) {
  return {
    type: 'UPDATE_BLOCK_LIST_SETTINGS',
    clientId: clientId,
    settings: settings
  };
}
/**
 * Returns an action object used in signalling that the block editor settings have been updated.
 *
 * @param {Object} settings Updated settings
 *
 * @return {Object} Action object
 */


function updateSettings(settings) {
  return {
    type: 'UPDATE_SETTINGS',
    settings: settings
  };
}
/**
 * Returns an action object used in signalling that a temporary reusable blocks have been saved
 * in order to switch its temporary id with the real id.
 *
 * @param {string} id        Reusable block's id.
 * @param {string} updatedId Updated block's id.
 *
 * @return {Object} Action object.
 */


function __unstableSaveReusableBlock(id, updatedId) {
  return {
    type: 'SAVE_REUSABLE_BLOCK_SUCCESS',
    id: id,
    updatedId: updatedId
  };
}
/**
 * Returns an action object used in signalling that the last block change should be marked explicitely as persistent.
 *
 * @return {Object} Action object.
 */


function __unstableMarkLastChangeAsPersistent() {
  return {
    type: 'MARK_LAST_CHANGE_AS_PERSISTENT'
  };
}
/**
 * Returns an action object used in signalling that the next block change should be marked explicitly as not persistent.
 *
 * @return {Object} Action object.
 */


function __unstableMarkNextChangeAsNotPersistent() {
  return {
    type: 'MARK_NEXT_CHANGE_AS_NOT_PERSISTENT'
  };
}
/**
 * Returns an action object used in signalling that the last block change is
 * an automatic change, meaning it was not performed by the user, and can be
 * undone using the `Escape` and `Backspace` keys. This action must be called
 * after the change was made, and any actions that are a consequence of it, so
 * it is recommended to be called at the next idle period to ensure all
 * selection changes have been recorded.
 *
 * @return {Object} Action object.
 */


function __unstableMarkAutomaticChange() {
  return {
    type: 'MARK_AUTOMATIC_CHANGE'
  };
}
/**
 * Generators that triggers an action used to enable or disable the navigation mode.
 *
 * @param {string} isNavigationMode Enable/Disable navigation mode.
 */


function setNavigationMode() {
  var isNavigationMode,
      _args9 = arguments;
  return _regenerator.default.wrap(function setNavigationMode$(_context9) {
    while (1) {
      switch (_context9.prev = _context9.next) {
        case 0:
          isNavigationMode = _args9.length > 0 && _args9[0] !== undefined ? _args9[0] : true;
          _context9.next = 3;
          return {
            type: 'SET_NAVIGATION_MODE',
            isNavigationMode: isNavigationMode
          };

        case 3:
          if (isNavigationMode) {
            (0, _a11y.speak)((0, _i18n.__)('You are currently in navigation mode. Navigate blocks using the Tab key and Arrow keys. Use Left and Right Arrow keys to move between nesting levels. To exit navigation mode and edit the selected block, press Enter.'));
          } else {
            (0, _a11y.speak)((0, _i18n.__)('You are currently in edit mode. To return to the navigation mode, press Escape.'));
          }

        case 4:
        case "end":
          return _context9.stop();
      }
    }
  }, _marked9);
}
/**
 * Generator that triggers an action used to enable or disable the block moving mode.
 *
 * @param {string|null} hasBlockMovingClientId Enable/Disable block moving mode.
 */


function setBlockMovingClientId() {
  var hasBlockMovingClientId,
      _args10 = arguments;
  return _regenerator.default.wrap(function setBlockMovingClientId$(_context10) {
    while (1) {
      switch (_context10.prev = _context10.next) {
        case 0:
          hasBlockMovingClientId = _args10.length > 0 && _args10[0] !== undefined ? _args10[0] : null;
          _context10.next = 3;
          return {
            type: 'SET_BLOCK_MOVING_MODE',
            hasBlockMovingClientId: hasBlockMovingClientId
          };

        case 3:
          if (hasBlockMovingClientId) {
            (0, _a11y.speak)((0, _i18n.__)('Use the Tab key and Arrow keys to choose new block location. Use Left and Right Arrow keys to move between nesting levels. Once location is selected press Enter or Space to move the block.'));
          }

        case 4:
        case "end":
          return _context10.stop();
      }
    }
  }, _marked10);
}
/**
 * Generator that triggers an action used to duplicate a list of blocks.
 *
 * @param {string[]} clientIds
 * @param {boolean} updateSelection
 */


function duplicateBlocks(clientIds) {
  var updateSelection,
      blocks,
      rootClientId,
      blockNames,
      lastSelectedIndex,
      clonedBlocks,
      _args11 = arguments;
  return _regenerator.default.wrap(function duplicateBlocks$(_context11) {
    while (1) {
      switch (_context11.prev = _context11.next) {
        case 0:
          updateSelection = _args11.length > 1 && _args11[1] !== undefined ? _args11[1] : true;

          if (!(!clientIds && !clientIds.length)) {
            _context11.next = 3;
            break;
          }

          return _context11.abrupt("return");

        case 3:
          _context11.next = 5;
          return (0, _controls.select)('core/block-editor', 'getBlocksByClientId', clientIds);

        case 5:
          blocks = _context11.sent;
          _context11.next = 8;
          return (0, _controls.select)('core/block-editor', 'getBlockRootClientId', clientIds[0]);

        case 8:
          rootClientId = _context11.sent;

          if (!(0, _lodash.some)(blocks, function (block) {
            return !block;
          })) {
            _context11.next = 11;
            break;
          }

          return _context11.abrupt("return");

        case 11:
          blockNames = blocks.map(function (block) {
            return block.name;
          }); // Return early if blocks don't support multiple usage.

          if (!(0, _lodash.some)(blockNames, function (blockName) {
            return !(0, _blocks.hasBlockSupport)(blockName, 'multiple', true);
          })) {
            _context11.next = 14;
            break;
          }

          return _context11.abrupt("return");

        case 14:
          _context11.next = 16;
          return (0, _controls.select)('core/block-editor', 'getBlockIndex', (0, _lodash.last)((0, _lodash.castArray)(clientIds)), rootClientId);

        case 16:
          lastSelectedIndex = _context11.sent;
          clonedBlocks = blocks.map(function (block) {
            return (0, _blocks.cloneBlock)(block);
          });
          _context11.next = 20;
          return insertBlocks(clonedBlocks, lastSelectedIndex + 1, rootClientId, updateSelection);

        case 20:
          if (!(clonedBlocks.length > 1 && updateSelection)) {
            _context11.next = 23;
            break;
          }

          _context11.next = 23;
          return multiSelect((0, _lodash.first)(clonedBlocks).clientId, (0, _lodash.last)(clonedBlocks).clientId);

        case 23:
          return _context11.abrupt("return", clonedBlocks.map(function (block) {
            return block.clientId;
          }));

        case 24:
        case "end":
          return _context11.stop();
      }
    }
  }, _marked11);
}
/**
 * Generator used to insert an empty block after a given block.
 *
 * @param {string} clientId
 */


function insertBeforeBlock(clientId) {
  var rootClientId, isLocked, firstSelectedIndex;
  return _regenerator.default.wrap(function insertBeforeBlock$(_context12) {
    while (1) {
      switch (_context12.prev = _context12.next) {
        case 0:
          if (clientId) {
            _context12.next = 2;
            break;
          }

          return _context12.abrupt("return");

        case 2:
          _context12.next = 4;
          return (0, _controls.select)('core/block-editor', 'getBlockRootClientId', clientId);

        case 4:
          rootClientId = _context12.sent;
          _context12.next = 7;
          return (0, _controls.select)('core/block-editor', 'getTemplateLock', rootClientId);

        case 7:
          isLocked = _context12.sent;

          if (!isLocked) {
            _context12.next = 10;
            break;
          }

          return _context12.abrupt("return");

        case 10:
          _context12.next = 12;
          return (0, _controls.select)('core/block-editor', 'getBlockIndex', clientId, rootClientId);

        case 12:
          firstSelectedIndex = _context12.sent;
          _context12.next = 15;
          return insertDefaultBlock({}, rootClientId, firstSelectedIndex);

        case 15:
          return _context12.abrupt("return", _context12.sent);

        case 16:
        case "end":
          return _context12.stop();
      }
    }
  }, _marked12);
}
/**
 * Generator used to insert an empty block before a given block.
 *
 * @param {string} clientId
 */


function insertAfterBlock(clientId) {
  var rootClientId, isLocked, firstSelectedIndex;
  return _regenerator.default.wrap(function insertAfterBlock$(_context13) {
    while (1) {
      switch (_context13.prev = _context13.next) {
        case 0:
          if (clientId) {
            _context13.next = 2;
            break;
          }

          return _context13.abrupt("return");

        case 2:
          _context13.next = 4;
          return (0, _controls.select)('core/block-editor', 'getBlockRootClientId', clientId);

        case 4:
          rootClientId = _context13.sent;
          _context13.next = 7;
          return (0, _controls.select)('core/block-editor', 'getTemplateLock', rootClientId);

        case 7:
          isLocked = _context13.sent;

          if (!isLocked) {
            _context13.next = 10;
            break;
          }

          return _context13.abrupt("return");

        case 10:
          _context13.next = 12;
          return (0, _controls.select)('core/block-editor', 'getBlockIndex', clientId, rootClientId);

        case 12:
          firstSelectedIndex = _context13.sent;
          _context13.next = 15;
          return insertDefaultBlock({}, rootClientId, firstSelectedIndex + 1);

        case 15:
          return _context13.abrupt("return", _context13.sent);

        case 16:
        case "end":
          return _context13.stop();
      }
    }
  }, _marked13);
}
/**
 * Returns an action object that toggles the highlighted block state.
 *
 * @param {string} clientId The block's clientId.
 * @param {boolean} isHighlighted The highlight state.
 */


function toggleBlockHighlight(clientId, isHighlighted) {
  return {
    type: 'TOGGLE_BLOCK_HIGHLIGHT',
    clientId: clientId,
    isHighlighted: isHighlighted
  };
}
/**
 * Yields action objects used in signalling that the block corresponding to the
 * given clientId should appear to "flash" by rhythmically highlighting it.
 *
 * @param {string} clientId Target block client ID.
 */


function flashBlock(clientId) {
  return _regenerator.default.wrap(function flashBlock$(_context14) {
    while (1) {
      switch (_context14.prev = _context14.next) {
        case 0:
          _context14.next = 2;
          return toggleBlockHighlight(clientId, true);

        case 2:
          _context14.next = 4;
          return {
            type: 'SLEEP',
            duration: 150
          };

        case 4:
          _context14.next = 6;
          return toggleBlockHighlight(clientId, false);

        case 6:
        case "end":
          return _context14.stop();
      }
    }
  }, _marked14);
}
/**
 * Returns an action object that sets whether the block has controlled innerblocks.
 *
 * @param {string} clientId The block's clientId.
 * @param {boolean} hasControlledInnerBlocks True if the block's inner blocks are controlled.
 */


function setHasControlledInnerBlocks(clientId, hasControlledInnerBlocks) {
  return {
    type: 'SET_HAS_CONTROLLED_INNER_BLOCKS',
    hasControlledInnerBlocks: hasControlledInnerBlocks,
    clientId: clientId
  };
}
//# sourceMappingURL=actions.js.map