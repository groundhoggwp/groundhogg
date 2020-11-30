import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { first, last, omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { useRef, useEffect, useState, useContext, forwardRef } from '@wordpress/element';
import { focus, isTextField, placeCaretAtHorizontalEdge } from '@wordpress/dom';
import { ENTER, BACKSPACE, DELETE } from '@wordpress/keycodes';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import deprecated from '@wordpress/deprecated';
/**
 * Internal dependencies
 */

import { isInsideRootBlock } from '../../utils/dom';
import useMovingAnimation from '../use-moving-animation';
import { Context, SetBlockNodes } from './root-container';
import { BlockListBlockContext } from './block';
import ELEMENTS from './block-wrapper-elements';
/**
 * This hook is used to lighly mark an element as a block element. Call this
 * hook and pass the returned props to the element to mark as a block. If you
 * define a ref for the element, it is important to pass the ref to this hook,
 * which the hooks in turn will pass to the component through the props it
 * returns. Optionally, you can also pass any other props through this hook, and
 * they will be merged and returned.
 *
 * @param {Object}  props   Optional. Props to pass to the element. Must contain
 *                          the ref if one is defined.
 * @param {Object}  options Options for internal use only.
 * @param {boolean} options.__unstableIsHtml
 *
 * @return {Object} Props to pass to the element to mark as a block.
 */

export function useBlockWrapperProps() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
      __unstableIsHtml = _ref.__unstableIsHtml;

  var fallbackRef = useRef();
  var ref = props.ref || fallbackRef;
  var onSelectionStart = useContext(Context);
  var setBlockNodes = useContext(SetBlockNodes);

  var _useContext = useContext(BlockListBlockContext),
      clientId = _useContext.clientId,
      rootClientId = _useContext.rootClientId,
      isSelected = _useContext.isSelected,
      isFirstMultiSelected = _useContext.isFirstMultiSelected,
      isLastMultiSelected = _useContext.isLastMultiSelected,
      isPartOfMultiSelection = _useContext.isPartOfMultiSelection,
      enableAnimation = _useContext.enableAnimation,
      index = _useContext.index,
      className = _useContext.className,
      name = _useContext.name,
      mode = _useContext.mode,
      blockTitle = _useContext.blockTitle,
      _useContext$wrapperPr = _useContext.wrapperProps,
      wrapperProps = _useContext$wrapperPr === void 0 ? {} : _useContext$wrapperPr;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSelectedBlocksInitialCaretPosition = _select.getSelectedBlocksInitialCaretPosition,
        _isMultiSelecting = _select.isMultiSelecting,
        _isNavigationMode = _select.isNavigationMode;

    return {
      shouldFocusFirstElement: isSelected && !_isMultiSelecting() && !_isNavigationMode(),
      initialPosition: isSelected ? getSelectedBlocksInitialCaretPosition() : undefined,
      isNavigationMode: _isNavigationMode
    };
  }, [isSelected]),
      initialPosition = _useSelect.initialPosition,
      shouldFocusFirstElement = _useSelect.shouldFocusFirstElement,
      isNavigationMode = _useSelect.isNavigationMode;

  var _useDispatch = useDispatch('core/block-editor'),
      insertDefaultBlock = _useDispatch.insertDefaultBlock,
      removeBlock = _useDispatch.removeBlock;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isHovered = _useState2[0],
      setHovered = _useState2[1]; // Provide the selected node, or the first and last nodes of a multi-
  // selection, so it can be used to position the contextual block toolbar.
  // We only provide what is necessary, and remove the nodes again when they
  // are no longer selected.


  useEffect(function () {
    if (isSelected || isFirstMultiSelected || isLastMultiSelected) {
      var node = ref.current;
      setBlockNodes(function (nodes) {
        return _objectSpread(_objectSpread({}, nodes), {}, _defineProperty({}, clientId, node));
      });
      return function () {
        setBlockNodes(function (nodes) {
          return omit(nodes, clientId);
        });
      };
    }
  }, [isSelected, isFirstMultiSelected, isLastMultiSelected]); // Set new block node if it changes.
  // This effect should happen on every render, so no dependencies should be
  // added.

  useEffect(function () {
    var node = ref.current;
    setBlockNodes(function (nodes) {
      if (!nodes[clientId] || nodes[clientId] === node) {
        return nodes;
      }

      return _objectSpread(_objectSpread({}, nodes), {}, _defineProperty({}, clientId, node));
    });
  }); // translators: %s: Type of block (i.e. Text, Image etc)

  var blockLabel = sprintf(__('Block: %s'), blockTitle); // Handing the focus of the block on creation and update

  /**
   * When a block becomes selected, transition focus to an inner tabbable.
   */

  var focusTabbable = function focusTabbable() {
    var ownerDocument = ref.current.ownerDocument; // Focus is captured by the wrapper node, so while focus transition
    // should only consider tabbables within editable display, since it
    // may be the wrapper itself or a side control which triggered the
    // focus event, don't unnecessary transition to an inner tabbable.

    if (ownerDocument.activeElement && isInsideRootBlock(ref.current, ownerDocument.activeElement)) {
      return;
    } // Find all tabbables within node.


    var textInputs = focus.tabbable.find(ref.current).filter(function (node) {
      return isTextField(node) && // Exclude inner blocks and block appenders
      isInsideRootBlock(ref.current, node) && !node.closest('.block-list-appender');
    }); // If reversed (e.g. merge via backspace), use the last in the set of
    // tabbables.

    var isReverse = -1 === initialPosition;
    var target = (isReverse ? last : first)(textInputs) || ref.current;
    placeCaretAtHorizontalEdge(target, isReverse);
  };

  useEffect(function () {
    if (shouldFocusFirstElement) {
      focusTabbable();
    }
  }, [shouldFocusFirstElement]); // Block Reordering animation

  useMovingAnimation(ref, isSelected || isPartOfMultiSelection, isSelected || isFirstMultiSelected, enableAnimation, index);
  useEffect(function () {
    if (!isSelected) {
      return;
    }
    /**
     * Interprets keydown event intent to remove or insert after block if
     * key event occurs on wrapper node. This can occur when the block has
     * no text fields of its own, particularly after initial insertion, to
     * allow for easy deletion and continuous writing flow to add additional
     * content.
     *
     * @param {KeyboardEvent} event Keydown event.
     */


    function onKeyDown(event) {
      var keyCode = event.keyCode,
          target = event.target;

      if (keyCode !== ENTER && keyCode !== BACKSPACE && keyCode !== DELETE) {
        return;
      }

      if (target !== ref.current || isTextField(target)) {
        return;
      }

      event.preventDefault();

      if (keyCode === ENTER) {
        insertDefaultBlock({}, rootClientId, index + 1);
      } else {
        removeBlock(clientId);
      }
    }

    function onMouseLeave(_ref2) {
      var buttons = _ref2.buttons;

      // The primary button must be pressed to initiate selection.
      // See https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/buttons
      if (buttons === 1) {
        onSelectionStart(clientId);
      }
    }

    ref.current.addEventListener('keydown', onKeyDown);
    ref.current.addEventListener('mouseleave', onMouseLeave);
    return function () {
      ref.current.removeEventListener('mouseleave', onMouseLeave);
      ref.current.removeEventListener('keydown', onKeyDown);
    };
  }, [isSelected, onSelectionStart, insertDefaultBlock, removeBlock]);
  useEffect(function () {
    if (!isNavigationMode) {
      return;
    }

    function onMouseOver(event) {
      if (event.defaultPrevented) {
        return;
      }

      event.preventDefault();

      if (isHovered) {
        return;
      }

      setHovered(true);
    }

    function onMouseOut(event) {
      if (event.defaultPrevented) {
        return;
      }

      event.preventDefault();

      if (!isHovered) {
        return;
      }

      setHovered(false);
    }

    ref.current.addEventListener('mouseover', onMouseOver);
    ref.current.addEventListener('mouseout', onMouseOut);
    return function () {
      ref.current.removeEventListener('mouseover', onMouseOver);
      ref.current.removeEventListener('mouseout', onMouseOut);
    };
  }, [isNavigationMode, isHovered, setHovered]);
  var htmlSuffix = mode === 'html' && !__unstableIsHtml ? '-visual' : '';
  return _objectSpread(_objectSpread(_objectSpread({}, wrapperProps), props), {}, {
    ref: ref,
    id: "block-".concat(clientId).concat(htmlSuffix),
    tabIndex: 0,
    role: 'group',
    'aria-label': blockLabel,
    'data-block': clientId,
    'data-type': name,
    'data-title': blockTitle,
    className: classnames(className, props.className, wrapperProps.className, {
      'is-hovered': isHovered
    }),
    style: _objectSpread(_objectSpread({}, wrapperProps.style), props.style)
  });
}
var BlockComponent = forwardRef(function (_ref3, ref) {
  var children = _ref3.children,
      _ref3$tagName = _ref3.tagName,
      TagName = _ref3$tagName === void 0 ? 'div' : _ref3$tagName,
      props = _objectWithoutProperties(_ref3, ["children", "tagName"]);

  deprecated('wp.blockEditor.__experimentalBlock', {
    alternative: 'wp.blockEditor.__experimentalUseBlockWrapperProps'
  });
  var blockWrapperProps = useBlockWrapperProps(_objectSpread(_objectSpread({}, props), {}, {
    ref: ref
  }));
  return createElement(TagName, blockWrapperProps, children);
});
var ExtendedBlockComponent = ELEMENTS.reduce(function (acc, element) {
  acc[element] = forwardRef(function (props, ref) {
    return createElement(BlockComponent, _extends({}, props, {
      ref: ref,
      tagName: element
    }));
  });
  return acc;
}, BlockComponent);
export var Block = ExtendedBlockComponent;
//# sourceMappingURL=block-wrapper.js.map