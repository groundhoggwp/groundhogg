/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
var transforms = {
  to: [{
    type: 'block',
    blocks: ['core/columns'],
    transform: function transform(_ref) {
      var className = _ref.className,
          columns = _ref.columns,
          content = _ref.content,
          width = _ref.width;
      return createBlock('core/columns', {
        align: 'wide' === width || 'full' === width ? width : undefined,
        className: className,
        columns: columns
      }, content.map(function (_ref2) {
        var children = _ref2.children;
        return createBlock('core/column', {}, [createBlock('core/paragraph', {
          content: children
        })]);
      }));
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map