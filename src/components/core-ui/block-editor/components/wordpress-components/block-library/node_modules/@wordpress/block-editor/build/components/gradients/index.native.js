"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _useGradient = require("./use-gradient");

Object.keys(_useGradient).forEach(function (key) {
  if (key === "default" || key === "__esModule") return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _useGradient[key];
    }
  });
});

var _withGradient = require("./with-gradient");

Object.keys(_withGradient).forEach(function (key) {
  if (key === "default" || key === "__esModule") return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _withGradient[key];
    }
  });
});
//# sourceMappingURL=index.native.js.map