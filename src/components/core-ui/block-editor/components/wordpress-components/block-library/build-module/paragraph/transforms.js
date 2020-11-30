/**
 * WordPress dependencies
 */
import { createBlock, getBlockAttributes } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

var _name$category$attrib = {
  name: "core/paragraph",
  category: "text",
  attributes: {
    align: {
      type: "string"
    },
    content: {
      type: "string",
      source: "html",
      selector: "p",
      "default": ""
    },
    dropCap: {
      type: "boolean",
      "default": false
    },
    placeholder: {
      type: "string"
    },
    direction: {
      type: "string",
      "enum": ["ltr", "rtl"]
    }
  },
  supports: {
    anchor: true,
    className: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      linkColor: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true,
    __experimentalSelector: "p",
    __unstablePasteTextInline: true
  }
},
    name = _name$category$attrib.name;
var transforms = {
  from: [{
    type: 'raw',
    // Paragraph is a fallback and should be matched last.
    priority: 20,
    selector: 'p',
    schema: function schema(_ref) {
      var phrasingContentSchema = _ref.phrasingContentSchema,
          isPaste = _ref.isPaste;
      return {
        p: {
          children: phrasingContentSchema,
          attributes: isPaste ? [] : ['style', 'id']
        }
      };
    },
    transform: function transform(node) {
      var attributes = getBlockAttributes(name, node.outerHTML);

      var _ref2 = node.style || {},
          textAlign = _ref2.textAlign;

      if (textAlign === 'left' || textAlign === 'center' || textAlign === 'right') {
        attributes.align = textAlign;
      }

      return createBlock(name, attributes);
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map