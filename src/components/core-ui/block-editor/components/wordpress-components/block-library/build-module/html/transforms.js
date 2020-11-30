/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/code'],
    transform: function transform(_ref) {
      var content = _ref.content;
      return createBlock('core/html', {
        content: content
      });
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map