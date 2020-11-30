"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useBlockWrapperProps = useBlockWrapperProps;
exports.Block = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _dom = require("@wordpress/dom");

var _keycodes = require("@wordpress/keycodes");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

var _dom2 = require("../../utils/dom");

var _useMovingAnimation = _interopRequireDefault(require("../use-moving-animation"));

var _rootContainer = require("./root-container");

var _block = require("./block");

var _blockWrapperElements = _interopRequireDefault(require("./block-wrapper-elements"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
function useBlockWrapperProps() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
      __unstableIsHtml = _ref.__unstableIsHtml;

  var fallbackRef = (0, _element.useRef)();
  var ref = props.ref || fallbackRef;
  var onSelectionStart = (0, _element.useContext)(_rootContainer.Context);
  var setBlockNodes = (0, _element.useContext)(_rootContainer.SetBlockNodes);

  var _useContext = (0, _element.useContext)(_block.BlockListBlockContext),
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

  var _useSelect = (0, _data.useSelect)(function (select) {
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

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      insertDefaultBlock = _useDispatch.insertDefaultBlock,
      removeBlock = _useDispatch.removeBlock;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isHovered = _useState2[0],
      setHovered = _useState2[1]; // Provide the selected node, or the first and last nodes of a multi-
  // selection, so it can be used to position the contextual block toolbar.
  // We only provide what is necessary, and remove the nodes again when they
  // are no longer selected.


  (0, _element.useEffect)(function () {
    if (isSelected || isFirstMultiSelected || isLastMultiSelected) {
      var node = ref.current;
      setBlockNodes(function (nodes) {
        return _objectSpread(_objectSpread({}, nodes), {}, (0, _defineProperty2.default)({}, clientId, node));
      });
      return function () {
        setBlockNodes(function (nodes) {
          return (0, _lodash.omit)(nodes, clientId);
        });
      };
    }
  }, [isSelected, isFirstMultiSelected, isLastMultiSelected]); // Set new block node if it changes.
  // This effect should happen on every render, so no dependencies should be
  // added.

  (0, _element.useEffect)(function () {
    var node = ref.current;
    setBlockNodes(function (nodes) {
      if (!nodes[clientId] || nodes[clientId] === node) {
        return nodes;
      }

      return _objectSpread(_objectSpread({}, nodes), {}, (0, _defineProperty2.default)({}, clientId, node));
    });
  }); // translators: %s: Type of block (i.e. Text, Image etc)

  var blockLabel = (0, _i18n.sprintf)((0, _i18n.__)('Block: %s'), blockTitle); // Handing the focus of the block on creation and update

  /**
   * When a block becomes selected, transition focus to an inner tabbable.
   */

  var focusTabbable = function focusTabbable() {
    var ownerDocument = ref.current.ownerDocument; // Focus is captured by the wrapper node, so while focus transition
    // should only consider tabbables within editable display, since it
    // may be the wrapper itself or a side control which triggered the
    // focus event, don't unnecessary transition to an inner tabbable.

    if (ownerDocument.activeElement && (0, _dom2.isInsideRootBlock)(ref.current, ownerDocument.activeElement)) {
      return;
    } // Find all tabbables within node.


    var textInputs = _dom.focus.tabbable.find(ref.current).filter(function (node) {
      return (0, _dom.isTextField)(node) && // Exclude inner blocks and block appenders
      (0, _dom2.isInsideRootBlock)(ref.current, node) && !node.closest('.block-list-appender');
    }); // If reversed (e.g. merge via backspace), use the last in the set of
    // tabbables.


    var isReverse = -1 === initialPosition;
    var target = (isReverse ? _lodash.last : _lodash.first)(textInputs) || ref.current;
    (0, _dom.placeCaretAtHorizontalEdge)(target, isReverse);
  };

  (0, _element.useEffect)(function () {
    if (shouldFocusFirstElement) {
      focusTabbable();
    }
  }, [shouldFocusFirstElement]); // Block Reordering animation

  (0, _useMovingAnimation.default)(ref, isSelected || isPartOfMultiSelection, isSelected || isFirstMultiSelected, enableAnimation, index);
  (0, _element.useEffect)(function () {
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

      if (keyCode !== _keycodes.ENTER && keyCode !== _keycodes.BACKSPACE && keyCode !== _keycodes.DELETE) {
        return;
      }

      if (target !== ref.current || (0, _dom.isTextField)(target)) {
        return;
      }

      event.preventDefault();

      if (keyCode === _keycodes.ENTER) {
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
  (0, _element.useEffect)(function () {
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
    className: (0, _classnames.default)(className, props.className, wrapperProps.className, {
      'is-hovered': isHovered
    }),
    style: _objectSpread(_objectSpread({}, wrapperProps.style), props.style)
  });
}

var BlockComponent = (0, _element.forwardRef)(function (_ref3, ref) {
  var children = _ref3.children,
      _ref3$tagName = _ref3.tagName,
      TagName = _ref3$tagName === void 0 ? 'div' : _ref3$tagName,
      props = (0, _objectWithoutProperties2.default)(_ref3, ["children", "tagName"]);
  (0, _deprecated.default)('wp.blockEditor.__experimentalBlock', {
    alternative: 'wp.blockEditor.__experimentalUseBlockWrapperProps'
  });
  var blockWrapperProps = useBlockWrapperProps(_objectSpread(_objectSpread({}, props), {}, {
    ref: ref
  }));
  return (0, _element.createElement)(TagName, blockWrapperProps, children);
});

var ExtendedBlockComponent = _blockWrapperElements.default.reduce(function (acc, element) {
  acc[element] = (0, _element.forwardRef)(function (props, ref) {
    return (0, _element.createElement)(BlockComponent, (0, _extends2.default)({}, props, {
      ref: ref,
      tagName: element
    }));
  });
  return acc;
}, BlockComponent);

var Block = ExtendedBlockComponent;
exports.Block = Block;
//# sourceMappingURL=block-wrapper.js.map