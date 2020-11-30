"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _blocks = require("@wordpress/blocks");

/**
 * WordPress dependencies
 */
var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/code'],
    transform: function transform(_ref) {
      var content = _ref.content;
      return (0, _blocks.createBlock)('core/html', {
        content: content
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map