"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.createRequestIdleCallback = createRequestIdleCallback;
exports.default = void 0;

/**
 * @typedef {( timeOrDeadline: IdleDeadline | number ) => void} Callback
 */

/**
 * @return {(callback: Callback) => void} RequestIdleCallback
 */
function createRequestIdleCallback() {
  if (typeof window === 'undefined') {
    return function (callback) {
      setTimeout(function () {
        return callback(Date.now());
      }, 0);
    };
  }

  return window.requestIdleCallback || window.requestAnimationFrame;
}

var _default = createRequestIdleCallback();

exports.default = _default;
//# sourceMappingURL=request-idle-callback.js.map