import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

/**
 * External dependencies
 */
import { useSpring } from 'react-spring/web.cjs';
/**
 * WordPress dependencies
 */

import { useState, useLayoutEffect, useReducer, useMemo } from '@wordpress/element';
import { useReducedMotion } from '@wordpress/compose';
import { getScrollContainer } from '@wordpress/dom';
/**
 * Simple reducer used to increment a counter.
 *
 * @param {number} state  Previous counter value.
 * @return {number} New state value.
 */

var counterReducer = function counterReducer(state) {
  return state + 1;
};

var getAbsolutePosition = function getAbsolutePosition(element) {
  return {
    top: element.offsetTop,
    left: element.offsetLeft
  };
};
/**
 * Hook used to compute the styles required to move a div into a new position.
 *
 * The way this animation works is the following:
 *  - It first renders the element as if there was no animation.
 *  - It takes a snapshot of the position of the block to use it
 *    as a destination point for the animation.
 *  - It restores the element to the previous position using a CSS transform
 *  - It uses the "resetAnimation" flag to reset the animation
 *    from the beginning in order to animate to the new destination point.
 *
 * @param {Object}  ref                      Reference to the element to animate.
 * @param {boolean} isSelected               Whether it's the current block or not.
 * @param {boolean} adjustScrolling          Adjust the scroll position to the current block.
 * @param {boolean} enableAnimation          Enable/Disable animation.
 * @param {*}       triggerAnimationOnChange Variable used to trigger the animation if it changes.
 */


function useMovingAnimation(ref, isSelected, adjustScrolling, enableAnimation, triggerAnimationOnChange) {
  var prefersReducedMotion = useReducedMotion() || !enableAnimation;

  var _useReducer = useReducer(counterReducer, 0),
      _useReducer2 = _slicedToArray(_useReducer, 2),
      triggeredAnimation = _useReducer2[0],
      triggerAnimation = _useReducer2[1];

  var _useReducer3 = useReducer(counterReducer, 0),
      _useReducer4 = _slicedToArray(_useReducer3, 2),
      finishedAnimation = _useReducer4[0],
      endAnimation = _useReducer4[1];

  var _useState = useState({
    x: 0,
    y: 0
  }),
      _useState2 = _slicedToArray(_useState, 2),
      transform = _useState2[0],
      setTransform = _useState2[1];

  var previous = useMemo(function () {
    return ref.current ? getAbsolutePosition(ref.current) : null;
  }, [triggerAnimationOnChange]); // Calculate the previous position of the block relative to the viewport and
  // return a function to maintain that position by scrolling.

  var preserveScrollPosition = useMemo(function () {
    if (!adjustScrolling || !ref.current) {
      return function () {};
    }

    var scrollContainer = getScrollContainer(ref.current);

    if (!scrollContainer) {
      return function () {};
    }

    var prevRect = ref.current.getBoundingClientRect();
    return function () {
      var blockRect = ref.current.getBoundingClientRect();
      var diff = blockRect.top - prevRect.top;

      if (diff) {
        scrollContainer.scrollTop += diff;
      }
    };
  }, [triggerAnimationOnChange, adjustScrolling]);
  useLayoutEffect(function () {
    if (triggeredAnimation) {
      endAnimation();
    }
  }, [triggeredAnimation]);
  useLayoutEffect(function () {
    if (!previous) {
      return;
    }

    if (prefersReducedMotion) {
      // if the animation is disabled and the scroll needs to be adjusted,
      // just move directly to the final scroll position.
      preserveScrollPosition();
      return;
    }

    ref.current.style.transform = '';
    var destination = getAbsolutePosition(ref.current);
    triggerAnimation();
    setTransform({
      x: Math.round(previous.left - destination.left),
      y: Math.round(previous.top - destination.top)
    });
  }, [triggerAnimationOnChange]); // Only called when either the x or y value changes.

  function onFrameChange(_ref) {
    var x = _ref.x,
        y = _ref.y;

    if (!ref.current) {
      return;
    }

    var isMoving = x === 0 && y === 0;
    ref.current.style.transformOrigin = isMoving ? '' : 'center';
    ref.current.style.transform = isMoving ? '' : "translate3d(".concat(x, "px,").concat(y, "px,0)");
    ref.current.style.zIndex = !isSelected || isMoving ? '' : '1';
    preserveScrollPosition();
  } // Called for every frame computed by useSpring.


  function onFrame(_ref2) {
    var x = _ref2.x,
        y = _ref2.y;
    x = Math.round(x);
    y = Math.round(y);

    if (x !== onFrame.x || y !== onFrame.y) {
      onFrameChange({
        x: x,
        y: y
      });
      onFrame.x = x;
      onFrame.y = y;
    }
  }

  onFrame.x = 0;
  onFrame.y = 0;
  useSpring({
    from: {
      x: transform.x,
      y: transform.y
    },
    to: {
      x: 0,
      y: 0
    },
    reset: triggeredAnimation !== finishedAnimation,
    config: {
      mass: 5,
      tension: 2000,
      friction: 200
    },
    immediate: prefersReducedMotion,
    onFrame: onFrame
  });
}

export default useMovingAnimation;
//# sourceMappingURL=index.js.map