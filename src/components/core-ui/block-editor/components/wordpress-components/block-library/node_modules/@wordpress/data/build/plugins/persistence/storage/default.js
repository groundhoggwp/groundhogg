"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _object = _interopRequireDefault(require("./object"));

/**
 * Internal dependencies
 */
var storage;

try {
  // Private Browsing in Safari 10 and earlier will throw an error when
  // attempting to set into localStorage. The test here is intentional in
  // causing a thrown error as condition for using fallback object storage.
  storage = window.localStorage;
  storage.setItem('__wpDataTestLocalStorage', '');
  storage.removeItem('__wpDataTestLocalStorage');
} catch (error) {
  storage = _object.default;
}

var _default = storage;
exports.default = _default;
//# sourceMappingURL=default.js.map