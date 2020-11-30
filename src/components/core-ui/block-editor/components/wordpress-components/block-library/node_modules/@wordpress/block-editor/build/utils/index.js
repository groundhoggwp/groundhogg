"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
var _exportNames = {
  transformStyles: true
};
Object.defineProperty(exports, "transformStyles", {
  enumerable: true,
  get: function get() {
    return _transformStyles.default;
  }
});

var _transformStyles = _interopRequireDefault(require("./transform-styles"));

var _theme = require("./theme");

Object.keys(_theme).forEach(function (key) {
  if (key === "default" || key === "__esModule") return;
  if (Object.prototype.hasOwnProperty.call(_exportNames, key)) return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _theme[key];
    }
  });
});
//# sourceMappingURL=index.js.map