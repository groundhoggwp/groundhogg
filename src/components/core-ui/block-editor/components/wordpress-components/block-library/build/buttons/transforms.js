"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _blocks = require("@wordpress/blocks");

/**
 * WordPress dependencies
 */

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
        (0, _blocks.createBlock)(name, {}, // Loop the selected buttons
        buttons.map(function (attributes) {
          return (// Create singular button in the buttons block
            (0, _blocks.createBlock)('core/button', attributes)
          );
        }))
      );
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map