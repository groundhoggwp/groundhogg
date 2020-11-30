/**
 * WordPress dependencies
 */
import { getScrollContainer } from '@wordpress/dom';
import { useCallback, useEffect, useRef } from '@wordpress/element';
var SCROLL_INACTIVE_DISTANCE_PX = 50;
var SCROLL_INTERVAL_MS = 25;
var PIXELS_PER_SECOND_PER_PERCENTAGE = 1000;
var VELOCITY_MULTIPLIER = PIXELS_PER_SECOND_PER_PERCENTAGE * (SCROLL_INTERVAL_MS / 1000);
/**
 * React hook that scrolls the scroll container when a block is being dragged.
 *
 * @return {Function[]} `startScrolling`, `scrollOnDragOver`, `stopScrolling`
 *                      functions to be called in `onDragStart`, `onDragOver`
 *                      and `onDragEnd` events respectively.
 */

export default function useScrollWhenDragging() {
  var dragStartY = useRef(null);
  var velocityY = useRef(null);
  var scrollParentY = useRef(null);
  var scrollEditorInterval = useRef(null); // Clear interval when unmounting.

  useEffect(function () {
    return function () {
      if (scrollEditorInterval.current) {
        clearInterval(scrollEditorInterval.current);
        scrollEditorInterval.current = null;
      }
    };
  }, []);
  var startScrolling = useCallback(function (event) {
    dragStartY.current = event.clientY; // Find nearest parent(s) to scroll.

    scrollParentY.current = getScrollContainer(event.target);
    scrollEditorInterval.current = setInterval(function () {
      if (scrollParentY.current && velocityY.current) {
        var newTop = scrollParentY.current.scrollTop + velocityY.current; // Setting `behavior: 'smooth'` as a scroll property seems to hurt performance.
        // Better to use a small scroll interval.

        scrollParentY.current.scroll({
          top: newTop
        });
      }
    }, SCROLL_INTERVAL_MS);
  }, []);
  var scrollOnDragOver = useCallback(function (event) {
    if (!scrollParentY.current) {
      return;
    }

    var scrollParentHeight = scrollParentY.current.offsetHeight;
    var offsetDragStartPosition = dragStartY.current - scrollParentY.current.offsetTop;
    var offsetDragPosition = event.clientY - scrollParentY.current.offsetTop;

    if (event.clientY > offsetDragStartPosition) {
      // User is dragging downwards.
      var moveableDistance = Math.max(scrollParentHeight - offsetDragStartPosition - SCROLL_INACTIVE_DISTANCE_PX, 0);
      var dragDistance = Math.max(offsetDragPosition - offsetDragStartPosition - SCROLL_INACTIVE_DISTANCE_PX, 0);
      var distancePercentage = dragDistance / moveableDistance;
      velocityY.current = VELOCITY_MULTIPLIER * distancePercentage;
    } else if (event.clientY < offsetDragStartPosition) {
      // User is dragging upwards.
      var _moveableDistance = Math.max(offsetDragStartPosition - SCROLL_INACTIVE_DISTANCE_PX, 0);

      var _dragDistance = Math.max(offsetDragStartPosition - offsetDragPosition - SCROLL_INACTIVE_DISTANCE_PX, 0);

      var _distancePercentage = _dragDistance / _moveableDistance;

      velocityY.current = -VELOCITY_MULTIPLIER * _distancePercentage;
    } else {
      velocityY.current = 0;
    }
  }, []);

  var stopScrolling = function stopScrolling() {
    dragStartY.current = null;
    scrollParentY.current = null;

    if (scrollEditorInterval.current) {
      clearInterval(scrollEditorInterval.current);
      scrollEditorInterval.current = null;
    }
  };

  return [startScrolling, scrollOnDragOver, stopScrolling];
}
//# sourceMappingURL=use-scroll-when-dragging.js.map