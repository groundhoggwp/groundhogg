/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return createBlock('core/verse', attributes);
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return createBlock('core/paragraph', attributes);
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map