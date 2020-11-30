"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ExpandedBlockControlsContainer;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactTransitionGroup = require("react-transition-group");

var _lodash = require("lodash");

var _warning = _interopRequireDefault(require("@wordpress/warning"));

var _blockControls = _interopRequireDefault(require("../block-controls"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

function ExpandedBlockControlsContainer(_ref) {
  var children = _ref.children,
      className = _ref.className;
  return (0, _element.createElement)(_blockControls.default.Slot, {
    __experimentalIsExpanded: true
  }, function (fills) {
    return (0, _element.createElement)(ExpandedBlockControlsHandler, {
      className: className,
      fills: fills
    }, children);
  });
}

function ExpandedBlockControlsHandler(_ref2) {
  var fills = _ref2.fills,
      _ref2$className = _ref2.className,
      className = _ref2$className === void 0 ? '' : _ref2$className,
      children = _ref2.children;
  var containerRef = (0, _element.useRef)();
  var fillsRef = (0, _element.useRef)();
  var toolbarRef = (0, _element.useRef)();

  var _useState = (0, _element.useState)({}),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      dimensions = _useState2[0],
      setDimensions = _useState2[1];

  var fillsPropRef = (0, _element.useRef)();
  fillsPropRef.current = fills;
  var resizeToolbar = (0, _element.useCallback)((0, _lodash.throttle)(function () {
    var toolbarContentElement = fillsPropRef.current.length ? fillsRef.current : toolbarRef.current;

    if (!toolbarContentElement) {
      return;
    }

    toolbarContentElement.style.position = 'absolute';
    toolbarContentElement.style.width = 'auto';
    var contentCSS = getComputedStyle(toolbarContentElement);
    setDimensions({
      width: contentCSS.getPropertyValue('width'),
      height: contentCSS.getPropertyValue('height')
    });
    toolbarContentElement.style.position = '';
    toolbarContentElement.style.width = '';
  }, 100), []);
  (0, _element.useEffect)(function () {
    var observer = new window.MutationObserver(function (mutationsList) {
      var hasChildList = mutationsList.find(function (_ref3) {
        var type = _ref3.type;
        return type === 'childList';
      });

      if (hasChildList) {
        resizeToolbar();
      }
    });
    observer.observe(containerRef.current, {
      childList: true,
      subtree: true
    });
    return function () {
      return observer.disconnect();
    };
  }, []);
  (0, _element.useEffect)(function () {
    if (fills.length > 1) {
      typeof process !== "undefined" && process.env && process.env.NODE_ENV !== "production" ? (0, _warning.default)("".concat(fills.length, " <BlockControls isExpanded> slots were registered but only one may be displayed.")) : void 0;
    }
  }, [fills.length]);
  var displayFill = fills[0];
  return (0, _element.createElement)("div", {
    className: "block-editor-block-toolbar-animated-width-container",
    ref: containerRef,
    style: dimensions
  }, (0, _element.createElement)(_reactTransitionGroup.TransitionGroup, null, displayFill ? (0, _element.createElement)(_reactTransitionGroup.CSSTransition, {
    key: "fills",
    timeout: 300,
    classNames: "block-editor-block-toolbar-content"
  }, (0, _element.createElement)("div", {
    className: className,
    ref: fillsRef
  }, displayFill)) : (0, _element.createElement)(_reactTransitionGroup.CSSTransition, {
    key: "default",
    timeout: 300,
    classNames: "block-editor-block-toolbar-content"
  }, (0, _element.createElement)("div", {
    className: className,
    ref: toolbarRef
  }, children))));
}
//# sourceMappingURL=expanded-block-controls-container.js.map