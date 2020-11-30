"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = InsertionPoint;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _components = require("@wordpress/components");

var _dom = require("@wordpress/dom");

var _inserter = _interopRequireDefault(require("../inserter"));

var _writingFlow = require("../writing-flow");

var _dom2 = require("../../utils/dom");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function InsertionPointInserter(_ref) {
  var clientId = _ref.clientId,
      setIsInserterForced = _ref.setIsInserterForced,
      containerRef = _ref.containerRef;
  var ref = (0, _element.useRef)(); // Hide the inserter above the selected block and during multi-selection.

  var isInserterHidden = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getMultiSelectedBlockClientIds = _select.getMultiSelectedBlockClientIds,
        getSelectedBlockClientId = _select.getSelectedBlockClientId,
        hasMultiSelection = _select.hasMultiSelection;

    var multiSelectedBlockClientIds = getMultiSelectedBlockClientIds();
    var selectedBlockClientId = getSelectedBlockClientId();
    return hasMultiSelection() ? multiSelectedBlockClientIds.includes(clientId) : clientId === selectedBlockClientId;
  }, [clientId]);

  function focusClosestTabbable(event) {
    var clientX = event.clientX,
        clientY = event.clientY,
        target = event.target; // Only handle click on the wrapper specifically, and not an event
    // bubbled from the inserter itself.

    if (target !== ref.current) {
      return;
    }

    var targetRect = target.getBoundingClientRect();
    var isReverse = clientY < targetRect.top + targetRect.height / 2;
    var blockNode = (0, _dom2.getBlockDOMNode)(clientId);
    var container = isReverse ? containerRef.current : blockNode;
    var closest = (0, _writingFlow.getClosestTabbable)(blockNode, true, container) || blockNode;
    var rect = new window.DOMRect(clientX, clientY, 0, 16);
    (0, _dom.placeCaretAtVerticalEdge)(closest, isReverse, rect, false);
  }

  return (
    /* eslint-disable-next-line jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0, _element.createElement)("div", {
      ref: ref,
      onFocus: function onFocus() {
        return setIsInserterForced(true);
      },
      onBlur: function onBlur() {
        return setIsInserterForced(false);
      },
      onClick: focusClosestTabbable // While ideally it would be enough to capture the
      // bubbling focus event from the Inserter, due to the
      // characteristics of click focusing of `button`s in
      // Firefox and Safari, it is not reliable.
      //
      // See: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
      ,
      tabIndex: -1,
      className: (0, _classnames.default)('block-editor-block-list__insertion-point-inserter', {
        'is-inserter-hidden': isInserterHidden
      })
    }, (0, _element.createElement)(_inserter.default, {
      position: "bottom center",
      clientId: clientId,
      __experimentalIsQuick: true
    }))
  );
}

function InsertionPointPopover(_ref2) {
  var clientId = _ref2.clientId,
      isInserterShown = _ref2.isInserterShown,
      isInserterForced = _ref2.isInserterForced,
      setIsInserterForced = _ref2.setIsInserterForced,
      containerRef = _ref2.containerRef,
      showInsertionPoint = _ref2.showInsertionPoint;
  var element = (0, _dom2.getBlockDOMNode)(clientId);
  return (0, _element.createElement)(_components.Popover, {
    noArrow: true,
    animate: false,
    anchorRef: element,
    position: "top right left",
    focusOnMount: false,
    className: "block-editor-block-list__insertion-point-popover",
    __unstableSlotName: "block-toolbar"
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-list__insertion-point",
    style: {
      width: element === null || element === void 0 ? void 0 : element.offsetWidth
    }
  }, showInsertionPoint && (0, _element.createElement)("div", {
    className: "block-editor-block-list__insertion-point-indicator"
  }), (isInserterShown || isInserterForced) && (0, _element.createElement)(InsertionPointInserter, {
    clientId: clientId,
    setIsInserterForced: setIsInserterForced,
    containerRef: containerRef
  })));
}

function InsertionPoint(_ref3) {
  var children = _ref3.children,
      containerRef = _ref3.containerRef;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isInserterShown = _useState2[0],
      setIsInserterShown = _useState2[1];

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isInserterForced = _useState4[0],
      setIsInserterForced = _useState4[1];

  var _useState5 = (0, _element.useState)(null),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      inserterClientId = _useState6[0],
      setInserterClientId = _useState6[1];

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select2 = select('core/block-editor'),
        _isMultiSelecting = _select2.isMultiSelecting,
        isBlockInsertionPointVisible = _select2.isBlockInsertionPointVisible,
        getBlockInsertionPoint = _select2.getBlockInsertionPoint,
        getBlockOrder = _select2.getBlockOrder;

    var insertionPoint = getBlockInsertionPoint();
    var order = getBlockOrder(insertionPoint.rootClientId);
    return {
      isMultiSelecting: _isMultiSelecting(),
      isInserterVisible: isBlockInsertionPointVisible(),
      selectedClientId: order[insertionPoint.index]
    };
  }, []),
      isMultiSelecting = _useSelect.isMultiSelecting,
      isInserterVisible = _useSelect.isInserterVisible,
      selectedClientId = _useSelect.selectedClientId;

  function onMouseMove(event) {
    if (!event.target.classList.contains('block-editor-block-list__layout')) {
      if (isInserterShown) {
        setIsInserterShown(false);
      }

      return;
    }

    var rect = event.target.getBoundingClientRect();
    var offset = event.clientY - rect.top;
    var element = Array.from(event.target.children).find(function (blockEl) {
      return blockEl.offsetTop > offset;
    });

    if (!element) {
      return;
    }

    var clientId = element.id.slice('block-'.length);

    if (!clientId) {
      return;
    }

    var elementRect = element.getBoundingClientRect();

    if (event.clientX > elementRect.right || event.clientX < elementRect.left) {
      if (isInserterShown) {
        setIsInserterShown(false);
      }

      return;
    }

    setIsInserterShown(true);
    setInserterClientId(clientId);
  }

  var isVisible = isInserterShown || isInserterForced || isInserterVisible;
  return (0, _element.createElement)(_element.Fragment, null, !isMultiSelecting && isVisible && (0, _element.createElement)(InsertionPointPopover, {
    clientId: isInserterVisible ? selectedClientId : inserterClientId,
    isInserterShown: isInserterShown,
    isInserterForced: isInserterForced,
    setIsInserterForced: setIsInserterForced,
    containerRef: containerRef,
    showInsertionPoint: isInserterVisible
  }), (0, _element.createElement)("div", {
    onMouseMove: !isInserterForced && !isMultiSelecting ? onMouseMove : undefined
  }, children));
}
//# sourceMappingURL=insertion-point.js.map