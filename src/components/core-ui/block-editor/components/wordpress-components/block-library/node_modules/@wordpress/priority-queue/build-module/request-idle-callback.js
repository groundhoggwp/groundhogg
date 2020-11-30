/**
 * @typedef {( timeOrDeadline: IdleDeadline | number ) => void} Callback
 */

/**
 * @return {(callback: Callback) => void} RequestIdleCallback
 */
export function createRequestIdleCallback() {
  if (typeof window === 'undefined') {
    return function (callback) {
      setTimeout(function () {
        return callback(Date.now());
      }, 0);
    };
  }

  return window.requestIdleCallback || window.requestAnimationFrame;
}
export default createRequestIdleCallback();
//# sourceMappingURL=request-idle-callback.js.map