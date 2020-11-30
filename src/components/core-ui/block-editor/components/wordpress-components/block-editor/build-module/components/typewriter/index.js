import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { Component, createRef } from '@wordpress/element';
import { computeCaretRect as _computeCaretRect, getScrollContainer } from '@wordpress/dom';
import { withSelect } from '@wordpress/data';
import { UP, DOWN, LEFT, RIGHT } from '@wordpress/keycodes';
/** @typedef {import('@wordpress/element').WPSyntheticEvent} WPSyntheticEvent */

var isIE = window.navigator.userAgent.indexOf('Trident') !== -1;
var arrowKeyCodes = new Set([UP, DOWN, LEFT, RIGHT]);
var initialTriggerPercentage = 0.75;

var Typewriter = /*#__PURE__*/function (_Component) {
  _inherits(Typewriter, _Component);

  var _super = _createSuper(Typewriter);

  function Typewriter() {
    var _this;

    _classCallCheck(this, Typewriter);

    _this = _super.apply(this, arguments);
    _this.ref = createRef();
    _this.onKeyDown = _this.onKeyDown.bind(_assertThisInitialized(_this));
    _this.addSelectionChangeListener = _this.addSelectionChangeListener.bind(_assertThisInitialized(_this));
    _this.computeCaretRectOnSelectionChange = _this.computeCaretRectOnSelectionChange.bind(_assertThisInitialized(_this));
    _this.maintainCaretPosition = _this.maintainCaretPosition.bind(_assertThisInitialized(_this));
    _this.computeCaretRect = _this.computeCaretRect.bind(_assertThisInitialized(_this));
    _this.onScrollResize = _this.onScrollResize.bind(_assertThisInitialized(_this));
    _this.isSelectionEligibleForScroll = _this.isSelectionEligibleForScroll.bind(_assertThisInitialized(_this));
    _this.getDocument = _this.getDocument.bind(_assertThisInitialized(_this));
    _this.getWindow = _this.getWindow.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(Typewriter, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // When the user scrolls or resizes, the scroll position should be
      // reset.
      this.getWindow().addEventListener('scroll', this.onScrollResize, true);
      this.getWindow().addEventListener('resize', this.onScrollResize, true);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.getWindow().removeEventListener('scroll', this.onScrollResize, true);
      this.getWindow().removeEventListener('resize', this.onScrollResize, true);
      this.getDocument().removeEventListener('selectionchange', this.computeCaretRectOnSelectionChange);

      if (this.onScrollResize.rafId) {
        this.getWindow().cancelAnimationFrame(this.onScrollResize.rafId);
      }

      if (this.onKeyDown.rafId) {
        this.getWindow().cancelAnimationFrame(this.onKeyDown.rafId);
      }
    }
  }, {
    key: "getDocument",
    value: function getDocument() {
      return this.ref.current.ownerDocument;
    }
  }, {
    key: "getWindow",
    value: function getWindow() {
      return this.getDocument().defaultView;
    }
    /**
     * Resets the scroll position to be maintained.
     */

  }, {
    key: "computeCaretRect",
    value: function computeCaretRect() {
      if (this.isSelectionEligibleForScroll()) {
        this.caretRect = _computeCaretRect(this.getWindow());
      }
    }
    /**
     * Resets the scroll position to be maintained during a `selectionchange`
     * event. Also removes the listener, so it acts as a one-time listener.
     */

  }, {
    key: "computeCaretRectOnSelectionChange",
    value: function computeCaretRectOnSelectionChange() {
      this.getDocument().removeEventListener('selectionchange', this.computeCaretRectOnSelectionChange);
      this.computeCaretRect();
    }
  }, {
    key: "onScrollResize",
    value: function onScrollResize() {
      var _this2 = this;

      if (this.onScrollResize.rafId) {
        return;
      }

      this.onScrollResize.rafId = this.getWindow().requestAnimationFrame(function () {
        _this2.computeCaretRect();

        delete _this2.onScrollResize.rafId;
      });
    }
    /**
     * Checks if the current situation is elegible for scroll:
     * - There should be one and only one block selected.
     * - The component must contain the selection.
     * - The active element must be contenteditable.
     */

  }, {
    key: "isSelectionEligibleForScroll",
    value: function isSelectionEligibleForScroll() {
      return this.props.selectedBlockClientId && this.ref.current.contains(this.getDocument().activeElement) && this.getDocument().activeElement.isContentEditable;
    }
  }, {
    key: "isLastEditableNode",
    value: function isLastEditableNode() {
      var editableNodes = this.ref.current.querySelectorAll('[contenteditable="true"]');
      var lastEditableNode = editableNodes[editableNodes.length - 1];
      return lastEditableNode === this.getDocument().activeElement;
    }
    /**
     * Maintains the scroll position after a selection change caused by a
     * keyboard event.
     *
     * @param {WPSyntheticEvent} event Synthetic keyboard event.
     */

  }, {
    key: "maintainCaretPosition",
    value: function maintainCaretPosition(_ref) {
      var keyCode = _ref.keyCode;

      if (!this.isSelectionEligibleForScroll()) {
        return;
      }

      var currentCaretRect = _computeCaretRect(this.getWindow());

      if (!currentCaretRect) {
        return;
      } // If for some reason there is no position set to be scrolled to, let
      // this be the position to be scrolled to in the future.


      if (!this.caretRect) {
        this.caretRect = currentCaretRect;
        return;
      } // Even though enabling the typewriter effect for arrow keys results in
      // a pleasant experience, it may not be the case for everyone, so, for
      // now, let's disable it.


      if (arrowKeyCodes.has(keyCode)) {
        // Reset the caret position to maintain.
        this.caretRect = currentCaretRect;
        return;
      }

      var diff = currentCaretRect.top - this.caretRect.top;

      if (diff === 0) {
        return;
      }

      var scrollContainer = getScrollContainer(this.ref.current); // The page must be scrollable.

      if (!scrollContainer) {
        return;
      }

      var windowScroll = scrollContainer === this.getDocument().body;
      var scrollY = windowScroll ? this.getWindow().scrollY : scrollContainer.scrollTop;
      var scrollContainerY = windowScroll ? 0 : scrollContainer.getBoundingClientRect().top;
      var relativeScrollPosition = windowScroll ? this.caretRect.top / this.getWindow().innerHeight : (this.caretRect.top - scrollContainerY) / (this.getWindow().innerHeight - scrollContainerY); // If the scroll position is at the start, the active editable element
      // is the last one, and the caret is positioned within the initial
      // trigger percentage of the page, do not scroll the page.
      // The typewriter effect should not kick in until an empty page has been
      // filled with the initial trigger percentage or the user scrolls
      // intentionally down.

      if (scrollY === 0 && relativeScrollPosition < initialTriggerPercentage && this.isLastEditableNode()) {
        // Reset the caret position to maintain.
        this.caretRect = currentCaretRect;
        return;
      }

      var scrollContainerHeight = windowScroll ? this.getWindow().innerHeight : scrollContainer.clientHeight; // Abort if the target scroll position would scroll the caret out of
      // view.

      if ( // The caret is under the lower fold.
      this.caretRect.top + this.caretRect.height > scrollContainerY + scrollContainerHeight || // The caret is above the upper fold.
      this.caretRect.top < scrollContainerY) {
        // Reset the caret position to maintain.
        this.caretRect = currentCaretRect;
        return;
      }

      if (windowScroll) {
        this.getWindow().scrollBy(0, diff);
      } else {
        scrollContainer.scrollTop += diff;
      }
    }
    /**
     * Adds a `selectionchange` listener to reset the scroll position to be
     * maintained.
     */

  }, {
    key: "addSelectionChangeListener",
    value: function addSelectionChangeListener() {
      this.getDocument().addEventListener('selectionchange', this.computeCaretRectOnSelectionChange);
    }
  }, {
    key: "onKeyDown",
    value: function onKeyDown(event) {
      var _this3 = this;

      event.persist(); // Ensure the any remaining request is cancelled.

      if (this.onKeyDown.rafId) {
        this.getWindow().cancelAnimationFrame(this.onKeyDown.rafId);
      } // Use an animation frame for a smooth result.


      this.onKeyDown.rafId = this.getWindow().requestAnimationFrame(function () {
        _this3.maintainCaretPosition(event);

        delete _this3.onKeyDown.rafId;
      });
    }
  }, {
    key: "render",
    value: function render() {
      // Disable reason: Wrapper itself is non-interactive, but must capture
      // bubbling events from children to determine focus transition intents.

      /* eslint-disable jsx-a11y/no-static-element-interactions */
      return createElement("div", {
        ref: this.ref,
        onKeyDown: this.onKeyDown,
        onKeyUp: this.maintainCaretPosition,
        onMouseDown: this.addSelectionChangeListener,
        onTouchStart: this.addSelectionChangeListener,
        className: "block-editor__typewriter"
      }, this.props.children);
      /* eslint-enable jsx-a11y/no-static-element-interactions */
    }
  }]);

  return Typewriter;
}(Component);
/**
 * The exported component. The implementation of Typewriter faced technical
 * challenges in Internet Explorer, and is simply skipped, rendering the given
 * props children instead.
 *
 * @type {WPComponent}
 */


var TypewriterOrIEBypass = isIE ? function (props) {
  return props.children;
} : withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  return {
    selectedBlockClientId: getSelectedBlockClientId()
  };
})(Typewriter);
/**
 * Ensures that the text selection keeps the same vertical distance from the
 * viewport during keyboard events within this component. The vertical distance
 * can vary. It is the last clicked or scrolled to position.
 */

export default TypewriterOrIEBypass;
//# sourceMappingURL=index.js.map