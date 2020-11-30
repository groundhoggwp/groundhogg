import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

/**
 * WordPress dependencies
 */
import { createBlock, getBlockAttributes } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { getLevelFromHeadingNodeName } from './shared';
var _name$category$attrib = {
  name: "core/heading",
  category: "text",
  attributes: {
    align: {
      type: "string"
    },
    content: {
      type: "string",
      source: "html",
      selector: "h1,h2,h3,h4,h5,h6",
      "default": ""
    },
    level: {
      type: "number",
      "default": 2
    },
    placeholder: {
      type: "string"
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
    __experimentalSelector: {
      "core/heading/h1": "h1",
      "core/heading/h2": "h2",
      "core/heading/h3": "h3",
      "core/heading/h4": "h4",
      "core/heading/h5": "h5",
      "core/heading/h6": "h6"
    },
    __unstablePasteTextInline: true
  }
},
    name = _name$category$attrib.name;
var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return attributes.map(function (_ref) {
        var content = _ref.content,
            anchor = _ref.anchor;
        return createBlock(name, {
          content: content,
          anchor: anchor
        });
      });
    }
  }, {
    type: 'raw',
    selector: 'h1,h2,h3,h4,h5,h6',
    schema: function schema(_ref2) {
      var phrasingContentSchema = _ref2.phrasingContentSchema,
          isPaste = _ref2.isPaste;
      var schema = {
        children: phrasingContentSchema,
        attributes: isPaste ? [] : ['style', 'id']
      };
      return {
        h1: schema,
        h2: schema,
        h3: schema,
        h4: schema,
        h5: schema,
        h6: schema
      };
    },
    transform: function transform(node) {
      var attributes = getBlockAttributes(name, node.outerHTML);

      var _ref3 = node.style || {},
          textAlign = _ref3.textAlign;

      attributes.level = getLevelFromHeadingNodeName(node.nodeName);

      if (textAlign === 'left' || textAlign === 'center' || textAlign === 'right') {
        attributes.align = textAlign;
      }

      return createBlock(name, attributes);
    }
  }].concat(_toConsumableArray([1, 2, 3, 4, 5, 6].map(function (level) {
    return {
      type: 'prefix',
      prefix: Array(level + 1).join('#'),
      transform: function transform(content) {
        return createBlock(name, {
          level: level,
          content: content
        });
      }
    };
  }))),
  to: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return attributes.map(function (_ref4) {
        var content = _ref4.content,
            anchor = _ref4.anchor;
        return createBlock('core/paragraph', {
          content: content,
          anchor: anchor
        });
      });
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map