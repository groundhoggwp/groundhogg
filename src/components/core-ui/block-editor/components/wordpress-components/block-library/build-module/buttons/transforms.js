/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

var _name$category$suppor = {
  name: "core/buttons",
  category: "design",
  supports: {
    anchor: true,
    align: true,
    alignWide: false,
    lightBlockWrapper: true
  }
},
    name = _name$category$suppor.name;
var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/button'],
    transform: function transform(buttons) {
      return (// Creates the buttons block
        createBlock(name, {}, // Loop the selected buttons
        buttons.map(function (attributes) {
          return (// Create singular button in the buttons block
            createBlock('core/button', attributes)
          );
        }))
      );
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map