import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { TransitionGroup, CSSTransition } from 'react-transition-group';
import { throttle } from 'lodash';
/**
 * WordPress dependencies
 */

import { useRef, useState, useEffect, useCallback } from '@wordpress/element';
import warning from '@wordpress/warning';
/**
 * Internal dependencies
 */

import BlockControls from '../block-controls';

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

export default function ExpandedBlockControlsContainer(_ref) {
  var children = _ref.children,
      className = _ref.className;
  return createElement(BlockControls.Slot, {
    __experimentalIsExpanded: true
  }, function (fills) {
    return createElement(ExpandedBlockControlsHandler, {
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
  var containerRef = useRef();
  var fillsRef = useRef();
  var toolbarRef = useRef();

  var _useState = useState({}),
      _useState2 = _slicedToArray(_useState, 2),
      dimensions = _useState2[0],
      setDimensions = _useState2[1];

  var fillsPropRef = useRef();
  fillsPropRef.current = fills;
  var resizeToolbar = useCallback(throttle(function () {
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
  useEffect(function () {
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
  useEffect(function () {
    if (fills.length > 1) {
      typeof process !== "undefined" && process.env && process.env.NODE_ENV !== "production" ? warning("".concat(fills.length, " <BlockControls isExpanded> slots were registered but only one may be displayed.")) : void 0;
    }
  }, [fills.length]);
  var displayFill = fills[0];
  return createElement("div", {
    className: "block-editor-block-toolbar-animated-width-container",
    ref: containerRef,
    style: dimensions
  }, createElement(TransitionGroup, null, displayFill ? createElement(CSSTransition, {
    key: "fills",
    timeout: 300,
    classNames: "block-editor-block-toolbar-content"
  }, createElement("div", {
    className: className,
    ref: fillsRef
  }, displayFill)) : createElement(CSSTransition, {
    key: "default",
    timeout: 300,
    classNames: "block-editor-block-toolbar-content"
  }, createElement("div", {
    className: className,
    ref: toolbarRef
  }, children))));
}
//# sourceMappingURL=expanded-block-controls-container.js.map