"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

/**
 * WordPress dependencies
 */
var _default = function _default(registry) {
  (0, _deprecated.default)('wp.data.plugins.controls', {
    hint: 'The controls plugins is now baked-in.'
  });
  return registry;
};

exports.default = _default;
//# sourceMappingURL=index.js.map