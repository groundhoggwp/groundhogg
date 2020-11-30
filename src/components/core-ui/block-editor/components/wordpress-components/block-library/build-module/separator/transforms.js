/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
  from: [{
    type: 'enter',
    regExp: /^-{3,}$/,
    transform: function transform() {
      return createBlock('core/separator');
    }
  }, {
    type: 'raw',
    selector: 'hr',
    schema: {
      hr: {}
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map