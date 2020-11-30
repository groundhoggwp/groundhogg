"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useSelect;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _useMemoOne = require("use-memo-one");

var _priorityQueue = require("@wordpress/priority-queue");

var _element = require("@wordpress/element");

var _isShallowEqual = _interopRequireDefault(require("@wordpress/is-shallow-equal"));

var _useRegistry = _interopRequireDefault(require("../registry-provider/use-registry"));

var _useAsyncMode = _interopRequireDefault(require("../async-mode-provider/use-async-mode"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Favor useLayoutEffect to ensure the store subscription callback always has
 * the selector from the latest render. If a store update happens between render
 * and the effect, this could cause missed/stale updates or inconsistent state.
 *
 * Fallback to useEffect for server rendered components because currently React
 * throws a warning when using useLayoutEffect in that environment.
 */
var useIsomorphicLayoutEffect = typeof window !== 'undefined' ? _element.useLayoutEffect : _element.useEffect;
var renderQueue = (0, _priorityQueue.createQueue)();
/**
 * Custom react hook for retrieving props from registered selectors.
 *
 * In general, this custom React hook follows the
 * [rules of hooks](https://reactjs.org/docs/hooks-rules.html).
 *
 * @param {Function} _mapSelect  Function called on every state change. The
 *                               returned value is exposed to the component
 *                               implementing this hook. The function receives
 *                               the `registry.select` method on the first
 *                               argument and the `registry` on the second
 *                               argument.
 * @param {Array}    deps        If provided, this memoizes the mapSelect so the
 *                               same `mapSelect` is invoked on every state
 *                               change unless the dependencies change.
 *
 * @example
 * ```js
 * const { useSelect } = wp.data;
 *
 * function HammerPriceDisplay( { currency } ) {
 *   const price = useSelect( ( select ) => {
 *     return select( 'my-shop' ).getPrice( 'hammer', currency )
 *   }, [ currency ] );
 *   return new Intl.NumberFormat( 'en-US', {
 *     style: 'currency',
 *     currency,
 *   } ).format( price );
 * }
 *
 * // Rendered in the application:
 * // <HammerPriceDisplay currency="USD" />
 * ```
 *
 * In the above example, when `HammerPriceDisplay` is rendered into an
 * application, the price will be retrieved from the store state using the
 * `mapSelect` callback on `useSelect`. If the currency prop changes then
 * any price in the state for that currency is retrieved. If the currency prop
 * doesn't change and other props are passed in that do change, the price will
 * not change because the dependency is just the currency.
 *
 * @return {Function}  A custom react hook.
 */

function useSelect(_mapSelect, deps) {
  var mapSelect = (0, _element.useCallback)(_mapSelect, deps);
  var registry = (0, _useRegistry.default)();
  var isAsync = (0, _useAsyncMode.default)(); // React can sometimes clear the `useMemo` cache.
  // We use the cache-stable `useMemoOne` to avoid
  // losing queues.

  var queueContext = (0, _useMemoOne.useMemoOne)(function () {
    return {
      queue: true
    };
  }, [registry]);

  var _useReducer = (0, _element.useReducer)(function (s) {
    return s + 1;
  }, 0),
      _useReducer2 = (0, _slicedToArray2.default)(_useReducer, 2),
      forceRender = _useReducer2[1];

  var latestMapSelect = (0, _element.useRef)();
  var latestIsAsync = (0, _element.useRef)(isAsync);
  var latestMapOutput = (0, _element.useRef)();
  var latestMapOutputError = (0, _element.useRef)();
  var isMountedAndNotUnsubscribing = (0, _element.useRef)();
  var mapOutput;

  try {
    if (latestMapSelect.current !== mapSelect || latestMapOutputError.current) {
      mapOutput = mapSelect(registry.select, registry);
    } else {
      mapOutput = latestMapOutput.current;
    }
  } catch (error) {
    var errorMessage = "An error occurred while running 'mapSelect': ".concat(error.message);

    if (latestMapOutputError.current) {
      errorMessage += "\nThe error may be correlated with this previous error:\n";
      errorMessage += "".concat(latestMapOutputError.current.stack, "\n\n");
      errorMessage += 'Original stack trace:';
      throw new Error(errorMessage);
    } else {
      // eslint-disable-next-line no-console
      console.error(errorMessage);
    }
  }

  useIsomorphicLayoutEffect(function () {
    latestMapSelect.current = mapSelect;
    latestMapOutput.current = mapOutput;
    latestMapOutputError.current = undefined;
    isMountedAndNotUnsubscribing.current = true; // This has to run after the other ref updates
    // to avoid using stale values in the flushed
    // callbacks or potentially overwriting a
    // changed `latestMapOutput.current`.

    if (latestIsAsync.current !== isAsync) {
      latestIsAsync.current = isAsync;
      renderQueue.flush(queueContext);
    }
  });
  useIsomorphicLayoutEffect(function () {
    var onStoreChange = function onStoreChange() {
      if (isMountedAndNotUnsubscribing.current) {
        try {
          var newMapOutput = latestMapSelect.current(registry.select, registry);

          if ((0, _isShallowEqual.default)(latestMapOutput.current, newMapOutput)) {
            return;
          }

          latestMapOutput.current = newMapOutput;
        } catch (error) {
          latestMapOutputError.current = error;
        }

        forceRender();
      }
    }; // catch any possible state changes during mount before the subscription
    // could be set.


    if (latestIsAsync.current) {
      renderQueue.add(queueContext, onStoreChange);
    } else {
      onStoreChange();
    }

    var unsubscribe = registry.subscribe(function () {
      if (latestIsAsync.current) {
        renderQueue.add(queueContext, onStoreChange);
      } else {
        onStoreChange();
      }
    });
    return function () {
      isMountedAndNotUnsubscribing.current = false;
      unsubscribe();
      renderQueue.flush(queueContext);
    };
  }, [registry]);
  return mapOutput;
}
//# sourceMappingURL=index.js.map