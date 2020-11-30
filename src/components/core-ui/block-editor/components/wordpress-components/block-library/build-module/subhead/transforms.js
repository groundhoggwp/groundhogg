/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
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