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
    blocks: ['core/code', 'core/paragraph'],
    transform: function transform(_ref) {
      var content = _ref.content,
          anchor = _ref.anchor;
      return (0, _blocks.createBlock)('core/preformatted', {
        content: content,
        anchor: anchor
      });
    }
  }, {
    type: 'raw',
    isMatch: function isMatch(node) {
      return node.nodeName === 'PRE' && !(node.children.length === 1 && node.firstChild.nodeName === 'CODE');
    },
    schema: function schema(_ref2) {
      var phrasingContentSchema = _ref2.phrasingContentSchema;
      return {
        pre: {
          children: phrasingContentSchema
        }
      };
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/paragraph', attributes);
    }
  }, {
    type: 'block',
    blocks: ['core/code'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/code', attributes);
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map