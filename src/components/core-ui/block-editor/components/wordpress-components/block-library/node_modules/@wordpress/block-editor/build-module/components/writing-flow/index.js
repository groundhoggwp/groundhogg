import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { overEvery, find, findLast, reverse, first, last } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useRef, useEffect, useState } from '@wordpress/element';
import { computeCaretRect, focus, isHorizontalEdge, isTextField, isVerticalEdge, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, isEntirelySelected } from '@wordpress/dom';
import { UP, DOWN, LEFT, RIGHT, TAB, isKeyboardEvent, ESCAPE, ENTER, SPACE } from '@wordpress/keycodes';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { isBlockFocusStop, isInSameBlock, hasInnerBlocksContext, isInsideRootBlock, getBlockDOMNode, getBlockClientId } from '../../utils/dom';
import FocusCapture from './focus-capture';

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}
/**
 * Given an element, returns true if the element is a tabbable text field, or
 * false otherwise.
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element is a tabbable text field.
 */


var isTabbableTextField = overEvery([isTextField, focus.tabbable.isTabbableIndex]);
/**
 * Returns true if the element should consider edge navigation upon a keyboard
 * event of the given directional key code, or false otherwise.
 *
 * @param {Element} element     HTML element to test.
 * @param {number}  keyCode     KeyboardEvent keyCode to test.
 * @param {boolean} hasModifier Whether a modifier is pressed.
 *
 * @return {boolean} Whether element should consider edge navigation.
 */

export function isNavigationCandidate(element, keyCode, hasModifier) {
  var isVertical = keyCode === UP || keyCode === DOWN; // Currently, all elements support unmodified vertical navigation.

  if (isVertical && !hasModifier) {
    return true;
  } // Native inputs should not navigate horizontally.


  var tagName = element.tagName;
  return tagName !== 'INPUT' && tagName !== 'TEXTAREA';
}
/**
 * Returns the optimal tab target from the given focused element in the
 * desired direction. A preference is made toward text fields, falling back
 * to the block focus stop if no other candidates exist for the block.
 *
 * @param {Element} target           Currently focused text field.
 * @param {boolean} isReverse        True if considering as the first field.
 * @param {Element} containerElement Element containing all blocks.
 * @param {boolean} onlyVertical     Wether to only consider tabbable elements
 *                                   that are visually above or under the
 *                                   target.
 *
 * @return {?Element} Optimal tab target, if one exists.
 */

export function getClosestTabbable(target, isReverse, containerElement, onlyVertical) {
  // Since the current focus target is not guaranteed to be a text field,
  // find all focusables. Tabbability is considered later.
  var focusableNodes = focus.focusable.find(containerElement);

  if (isReverse) {
    focusableNodes = reverse(focusableNodes);
  } // Consider as candidates those focusables after the current target.
  // It's assumed this can only be reached if the target is focusable
  // (on its keydown event), so no need to verify it exists in the set.


  focusableNodes = focusableNodes.slice(focusableNodes.indexOf(target) + 1);
  var targetRect;

  if (onlyVertical) {
    targetRect = target.getBoundingClientRect();
  }

  function isTabCandidate(node, i, array) {
    // Not a candidate if the node is not tabbable.
    if (!focus.tabbable.isTabbableIndex(node)) {
      return false;
    }

    if (onlyVertical) {
      var nodeRect = node.getBoundingClientRect();

      if (nodeRect.left >= targetRect.right || nodeRect.right <= targetRect.left) {
        return false;
      }
    } // Prefer text fields...


    if (isTextField(node)) {
      return true;
    } // ...but settle for block focus stop.


    if (!isBlockFocusStop(node)) {
      return false;
    } // If element contains inner blocks, stop immediately at its focus
    // wrapper.


    if (hasInnerBlocksContext(node)) {
      return true;
    } // If navigating out of a block (in reverse), don't consider its
    // block focus stop.


    if (node.contains(target)) {
      return false;
    } // In case of block focus stop, check to see if there's a better
    // text field candidate within.


    for (var offset = 1, nextNode; nextNode = array[i + offset]; offset++) {
      // Abort if no longer testing descendents of focus stop.
      if (!node.contains(nextNode)) {
        break;
      } // Apply same tests by recursion. This is important to consider
      // nestable blocks where we don't want to settle for the inner
      // block focus stop.


      if (isTabCandidate(nextNode, i + offset, array)) {
        return false;
      }
    }

    return true;
  }

  return find(focusableNodes, isTabCandidate);
}

function selector(select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getMultiSelectedBlocksStartClientId = _select.getMultiSelectedBlocksStartClientId,
      getMultiSelectedBlocksEndClientId = _select.getMultiSelectedBlocksEndClientId,
      getPreviousBlockClientId = _select.getPreviousBlockClientId,
      getNextBlockClientId = _select.getNextBlockClientId,
      getFirstMultiSelectedBlockClientId = _select.getFirstMultiSelectedBlockClientId,
      getLastMultiSelectedBlockClientId = _select.getLastMultiSelectedBlockClientId,
      hasMultiSelection = _select.hasMultiSelection,
      getBlockOrder = _select.getBlockOrder,
      isNavigationMode = _select.isNavigationMode,
      hasBlockMovingClientId = _select.hasBlockMovingClientId,
      getBlockIndex = _select.getBlockIndex,
      getBlockRootClientId = _select.getBlockRootClientId,
      getClientIdsOfDescendants = _select.getClientIdsOfDescendants,
      canInsertBlockType = _select.canInsertBlockType,
      getBlockName = _select.getBlockName,
      isSelectionEnabled = _select.isSelectionEnabled,
      getBlockSelectionStart = _select.getBlockSelectionStart,
      isMultiSelecting = _select.isMultiSelecting,
      getSettings = _select.getSettings;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectionStartClientId = getMultiSelectedBlocksStartClientId();
  var selectionEndClientId = getMultiSelectedBlocksEndClientId();
  return {
    selectedBlockClientId: selectedBlockClientId,
    selectionStartClientId: selectionStartClientId,
    selectionBeforeEndClientId: getPreviousBlockClientId(selectionEndClientId || selectedBlockClientId),
    selectionAfterEndClientId: getNextBlockClientId(selectionEndClientId || selectedBlockClientId),
    selectedFirstClientId: getFirstMultiSelectedBlockClientId(),
    selectedLastClientId: getLastMultiSelectedBlockClientId(),
    hasMultiSelection: hasMultiSelection(),
    blocks: getBlockOrder(),
    isNavigationMode: isNavigationMode(),
    hasBlockMovingClientId: hasBlockMovingClientId,
    getBlockIndex: getBlockIndex,
    getBlockRootClientId: getBlockRootClientId,
    getClientIdsOfDescendants: getClientIdsOfDescendants,
    canInsertBlockType: canInsertBlockType,
    getBlockName: getBlockName,
    isSelectionEnabled: isSelectionEnabled(),
    blockSelectionStart: getBlockSelectionStart(),
    isMultiSelecting: isMultiSelecting(),
    keepCaretInsideBlock: getSettings().keepCaretInsideBlock
  };
}
/**
 * Handles selection and navigation across blocks. This component should be
 * wrapped around BlockList.
 *
 * @param {Object}    props          Component properties.
 * @param {WPElement} props.children Children to be rendered.
 */


export default function WritingFlow(_ref) {
  var children = _ref.children;
  var container = useRef();
  var focusCaptureBeforeRef = useRef();
  var focusCaptureAfterRef = useRef();
  var multiSelectionContainer = useRef();
  var entirelySelected = useRef(); // Reference that holds the a flag for enabling or disabling
  // capturing on the focus capture elements.

  var noCapture = useRef(); // Here a DOMRect is stored while moving the caret vertically so vertical
  // position of the start position can be restored. This is to recreate
  // browser behaviour across blocks.

  var verticalRect = useRef();

  var _useSelect = useSelect(selector, []),
      selectedBlockClientId = _useSelect.selectedBlockClientId,
      selectionStartClientId = _useSelect.selectionStartClientId,
      selectionBeforeEndClientId = _useSelect.selectionBeforeEndClientId,
      selectionAfterEndClientId = _useSelect.selectionAfterEndClientId,
      selectedFirstClientId = _useSelect.selectedFirstClientId,
      selectedLastClientId = _useSelect.selectedLastClientId,
      hasMultiSelection = _useSelect.hasMultiSelection,
      blocks = _useSelect.blocks,
      isNavigationMode = _useSelect.isNavigationMode,
      hasBlockMovingClientId = _useSelect.hasBlockMovingClientId,
      isSelectionEnabled = _useSelect.isSelectionEnabled,
      blockSelectionStart = _useSelect.blockSelectionStart,
      isMultiSelecting = _useSelect.isMultiSelecting,
      getBlockIndex = _useSelect.getBlockIndex,
      getBlockRootClientId = _useSelect.getBlockRootClientId,
      getClientIdsOfDescendants = _useSelect.getClientIdsOfDescendants,
      canInsertBlockType = _useSelect.canInsertBlockType,
      getBlockName = _useSelect.getBlockName,
      keepCaretInsideBlock = _useSelect.keepCaretInsideBlock;

  var _useDispatch = useDispatch('core/block-editor'),
      multiSelect = _useDispatch.multiSelect,
      selectBlock = _useDispatch.selectBlock,
      clearSelectedBlock = _useDispatch.clearSelectedBlock,
      setNavigationMode = _useDispatch.setNavigationMode,
      setBlockMovingClientId = _useDispatch.setBlockMovingClientId,
      moveBlockToPosition = _useDispatch.moveBlockToPosition;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      canInsertMovingBlock = _useState2[0],
      setCanInsertMovingBlock = _useState2[1];

  function onMouseDown(event) {
    verticalRect.current = null; // Clicking inside a selected block should exit navigation mode and block moving mode.

    if (isNavigationMode && selectedBlockClientId && isInsideRootBlock(getBlockDOMNode(selectedBlockClientId), event.target)) {
      setNavigationMode(false);
      setBlockMovingClientId(null);
    } else if (isNavigationMode && hasBlockMovingClientId() && getBlockClientId(event.target)) {
      setCanInsertMovingBlock(canInsertBlockType(getBlockName(hasBlockMovingClientId()), getBlockRootClientId(getBlockClientId(event.target))));
    } // Multi-select blocks when Shift+clicking.


    if (isSelectionEnabled && // The main button.
    // https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/button
    event.button === 0) {
      var clientId = getBlockClientId(event.target);

      if (clientId) {
        if (event.shiftKey) {
          if (blockSelectionStart !== clientId) {
            multiSelect(blockSelectionStart, clientId);
            event.preventDefault();
          } // Allow user to escape out of a multi-selection to a singular
          // selection of a block via click. This is handled here since
          // focus handling excludes blocks when there is multiselection,
          // as focus can be incurred by starting a multiselection (focus
          // moved to first block's multi-controls).

        } else if (hasMultiSelection) {
          selectBlock(clientId);
        }
      }
    }
  }

  function expandSelection(isReverse) {
    var nextSelectionEndClientId = isReverse ? selectionBeforeEndClientId : selectionAfterEndClientId;

    if (nextSelectionEndClientId) {
      multiSelect(selectionStartClientId || selectedBlockClientId, nextSelectionEndClientId);
    }
  }

  function moveSelection(isReverse) {
    var focusedBlockClientId = isReverse ? selectedFirstClientId : selectedLastClientId;

    if (focusedBlockClientId) {
      selectBlock(focusedBlockClientId);
    }
  }
  /**
   * Returns true if the given target field is the last in its block which
   * can be considered for tab transition. For example, in a block with two
   * text fields, this would return true when reversing from the first of the
   * two fields, but false when reversing from the second.
   *
   * @param {Element} target    Currently focused text field.
   * @param {boolean} isReverse True if considering as the first field.
   *
   * @return {boolean} Whether field is at edge for tab transition.
   */


  function isTabbableEdge(target, isReverse) {
    var closestTabbable = getClosestTabbable(target, isReverse, container.current);
    return !closestTabbable || !isInSameBlock(target, closestTabbable);
  }

  function onKeyDown(event) {
    var keyCode = event.keyCode,
        target = event.target;
    var isUp = keyCode === UP;
    var isDown = keyCode === DOWN;
    var isLeft = keyCode === LEFT;
    var isRight = keyCode === RIGHT;
    var isTab = keyCode === TAB;
    var isEscape = keyCode === ESCAPE;
    var isEnter = keyCode === ENTER;
    var isSpace = keyCode === SPACE;
    var isReverse = isUp || isLeft;
    var isHorizontal = isLeft || isRight;
    var isVertical = isUp || isDown;
    var isNav = isHorizontal || isVertical;
    var isShift = event.shiftKey;
    var hasModifier = isShift || event.ctrlKey || event.altKey || event.metaKey;
    var isNavEdge = isVertical ? isVerticalEdge : isHorizontalEdge; // In navigation mode, tab and arrows navigate from block to block.

    if (isNavigationMode) {
      var navigateUp = isTab && isShift || isUp;
      var navigateDown = isTab && !isShift || isDown; // Move out of current nesting level (no effect if at root level).

      var navigateOut = isLeft; // Move into next nesting level (no effect if the current block has no innerBlocks).

      var navigateIn = isRight;
      var focusedBlockUid;

      if (navigateUp) {
        focusedBlockUid = selectionBeforeEndClientId;
      } else if (navigateDown) {
        focusedBlockUid = selectionAfterEndClientId;
      } else if (navigateOut) {
        var _getBlockRootClientId;

        focusedBlockUid = (_getBlockRootClientId = getBlockRootClientId(selectedBlockClientId)) !== null && _getBlockRootClientId !== void 0 ? _getBlockRootClientId : selectedBlockClientId;
      } else if (navigateIn) {
        var _getClientIdsOfDescen;

        focusedBlockUid = (_getClientIdsOfDescen = getClientIdsOfDescendants([selectedBlockClientId])[0]) !== null && _getClientIdsOfDescen !== void 0 ? _getClientIdsOfDescen : selectedBlockClientId;
      }

      var startingBlockClientId = hasBlockMovingClientId();

      if (startingBlockClientId && focusedBlockUid) {
        setCanInsertMovingBlock(canInsertBlockType(getBlockName(startingBlockClientId), getBlockRootClientId(focusedBlockUid)));
      }

      if (isEscape && startingBlockClientId) {
        setBlockMovingClientId(null);
        setCanInsertMovingBlock(false);
      }

      if ((isEnter || isSpace) && startingBlockClientId) {
        var sourceRoot = getBlockRootClientId(startingBlockClientId);
        var destRoot = getBlockRootClientId(selectedBlockClientId);
        var sourceBlockIndex = getBlockIndex(startingBlockClientId, sourceRoot);
        var destinationBlockIndex = getBlockIndex(selectedBlockClientId, destRoot);

        if (sourceBlockIndex < destinationBlockIndex && sourceRoot === destRoot) {
          destinationBlockIndex -= 1;
        }

        moveBlockToPosition(startingBlockClientId, sourceRoot, destRoot, destinationBlockIndex);
        selectBlock(startingBlockClientId);
        setBlockMovingClientId(null);
      }

      if (navigateDown || navigateUp || navigateOut || navigateIn) {
        if (focusedBlockUid) {
          event.preventDefault();
          selectBlock(focusedBlockUid);
        } else if (isTab && selectedBlockClientId) {
          var wrapper = getBlockDOMNode(selectedBlockClientId);
          var nextTabbable;

          if (navigateDown) {
            nextTabbable = focus.tabbable.findNext(wrapper);
          } else {
            nextTabbable = focus.tabbable.findPrevious(wrapper);
          }

          if (nextTabbable) {
            event.preventDefault();
            nextTabbable.focus();
            clearSelectedBlock();
          }
        }
      }

      return;
    } // In Edit mode, Tab should focus the first tabbable element after the
    // content, which is normally the sidebar (with block controls) and
    // Shift+Tab should focus the first tabbable element before the content,
    // which is normally the block toolbar.
    // Arrow keys can be used, and Tab and arrow keys can be used in
    // Navigation mode (press Esc), to navigate through blocks.


    if (selectedBlockClientId) {
      if (isTab) {
        var _wrapper = getBlockDOMNode(selectedBlockClientId);

        if (isShift) {
          if (target === _wrapper) {
            // Disable focus capturing on the focus capture element, so
            // it doesn't refocus this block and so it allows default
            // behaviour (moving focus to the next tabbable element).
            noCapture.current = true;
            focusCaptureBeforeRef.current.focus();
            return;
          }
        } else {
          var tabbables = focus.tabbable.find(_wrapper);

          var lastTabbable = last(tabbables) || _wrapper;

          if (target === lastTabbable) {
            // See comment above.
            noCapture.current = true;
            focusCaptureAfterRef.current.focus();
            return;
          }
        }
      } else if (isEscape) {
        setNavigationMode(true);
      }
    } else if (hasMultiSelection && isTab && target === multiSelectionContainer.current) {
      // See comment above.
      noCapture.current = true;

      if (isShift) {
        focusCaptureBeforeRef.current.focus();
      } else {
        focusCaptureAfterRef.current.focus();
      }

      return;
    }

    var ownerDocument = container.current.ownerDocument;
    var defaultView = ownerDocument.defaultView; // When presing any key other than up or down, the initial vertical
    // position must ALWAYS be reset. The vertical position is saved so it
    // can be restored as well as possible on sebsequent vertical arrow key
    // presses. It may not always be possible to restore the exact same
    // position (such as at an empty line), so it wouldn't be good to
    // compute the position right before any vertical arrow key press.

    if (!isVertical) {
      verticalRect.current = null;
    } else if (!verticalRect.current) {
      verticalRect.current = computeCaretRect(defaultView);
    } // This logic inside this condition needs to be checked before
    // the check for event.nativeEvent.defaultPrevented.
    // The logic handles meta+a keypress and this event is default prevented
    // by RichText.


    if (!isNav) {
      // Set immediately before the meta+a combination can be pressed.
      if (isKeyboardEvent.primary(event)) {
        entirelySelected.current = isEntirelySelected(target);
      }

      if (isKeyboardEvent.primary(event, 'a')) {
        // When the target is contentEditable, selection will already
        // have been set by the browser earlier in this call stack. We
        // need check the previous result, otherwise all blocks will be
        // selected right away.
        if (target.isContentEditable ? entirelySelected.current : isEntirelySelected(target)) {
          multiSelect(first(blocks), last(blocks));
          event.preventDefault();
        } // After pressing primary + A we can assume isEntirelySelected is true.
        // Calling right away isEntirelySelected after primary + A may still return false on some browsers.


        entirelySelected.current = true;
      }

      return;
    } // Abort if navigation has already been handled (e.g. RichText inline
    // boundaries).


    if (event.nativeEvent.defaultPrevented) {
      return;
    } // Abort if our current target is not a candidate for navigation (e.g.
    // preserve native input behaviors).


    if (!isNavigationCandidate(target, keyCode, hasModifier)) {
      return;
    } // In the case of RTL scripts, right means previous and left means next,
    // which is the exact reverse of LTR.


    var _getComputedStyle = getComputedStyle(target),
        direction = _getComputedStyle.direction;

    var isReverseDir = direction === 'rtl' ? !isReverse : isReverse;

    if (isShift) {
      if ( // Ensure that there is a target block.
      (isReverse && selectionBeforeEndClientId || !isReverse && selectionAfterEndClientId) && (hasMultiSelection || isTabbableEdge(target, isReverse) && isNavEdge(target, isReverse))) {
        // Shift key is down, and there is multi selection or we're at
        // the end of the current block.
        expandSelection(isReverse);
        event.preventDefault();
      }
    } else if (hasMultiSelection) {
      // Moving from block multi-selection to single block selection
      moveSelection(isReverse);
      event.preventDefault();
    } else if (isVertical && isVerticalEdge(target, isReverse) && !keepCaretInsideBlock) {
      var closestTabbable = getClosestTabbable(target, isReverse, container.current, true);

      if (closestTabbable) {
        placeCaretAtVerticalEdge(closestTabbable, isReverse, verticalRect.current);
        event.preventDefault();
      }
    } else if (isHorizontal && defaultView.getSelection().isCollapsed && isHorizontalEdge(target, isReverseDir) && !keepCaretInsideBlock) {
      var _closestTabbable = getClosestTabbable(target, isReverseDir, container.current);

      placeCaretAtHorizontalEdge(_closestTabbable, isReverseDir);
      event.preventDefault();
    }
  }

  function focusLastTextField() {
    var focusableNodes = focus.focusable.find(container.current);
    var target = findLast(focusableNodes, isTabbableTextField);

    if (target) {
      placeCaretAtHorizontalEdge(target, true);
    }
  }

  useEffect(function () {
    if (hasMultiSelection && !isMultiSelecting) {
      multiSelectionContainer.current.focus();
    }
  }, [hasMultiSelection, isMultiSelecting]);
  var className = classnames('block-editor-writing-flow', {
    'is-navigate-mode': isNavigationMode,
    'is-block-moving-mode': !!hasBlockMovingClientId(),
    'can-insert-moving-block': canInsertMovingBlock
  }); // Disable reason: Wrapper itself is non-interactive, but must capture
  // bubbling events from children to determine focus transition intents.

  /* eslint-disable jsx-a11y/no-static-element-interactions */

  return createElement("div", {
    className: className
  }, createElement(FocusCapture, {
    ref: focusCaptureBeforeRef,
    selectedClientId: selectedBlockClientId,
    containerRef: container,
    noCapture: noCapture,
    hasMultiSelection: hasMultiSelection,
    multiSelectionContainer: multiSelectionContainer
  }), createElement("div", {
    ref: container,
    onKeyDown: onKeyDown,
    onMouseDown: onMouseDown
  }, createElement("div", {
    ref: multiSelectionContainer,
    tabIndex: hasMultiSelection ? '0' : undefined,
    "aria-label": hasMultiSelection ? __('Multiple selected blocks') : undefined // Needs to be positioned within the viewport, so focus to this
    // element does not scroll the page.
    ,
    style: {
      position: 'fixed'
    }
  }), children), createElement(FocusCapture, {
    ref: focusCaptureAfterRef,
    selectedClientId: selectedBlockClientId,
    containerRef: container,
    noCapture: noCapture,
    hasMultiSelection: hasMultiSelection,
    multiSelectionContainer: multiSelectionContainer,
    isReverse: true
  }), createElement("div", {
    "aria-hidden": true,
    tabIndex: -1,
    onClick: focusLastTextField,
    className: "block-editor-writing-flow__click-redirect"
  }));
  /* eslint-enable jsx-a11y/no-static-element-interactions */
}
//# sourceMappingURL=index.js.map