/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/code', 'core/paragraph'],
    transform: function transform(_ref) {
      var content = _ref.content,
          anchor = _ref.anchor;
      return createBlock('core/preformatted', {
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
      return createBlock('core/paragraph', attributes);
    }
  }, {
    type: 'block',
    blocks: ['core/code'],
    transform: function transform(attributes) {
      return createBlock('core/code', attributes);
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map