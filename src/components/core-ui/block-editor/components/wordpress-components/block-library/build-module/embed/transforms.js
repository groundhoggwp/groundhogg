import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { renderToString } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/embed",
  category: "embed",
  attributes: {
    url: {
      type: "string"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    type: {
      type: "string"
    },
    providerNameSlug: {
      type: "string"
    },
    allowResponsive: {
      type: "boolean",
      "default": true
    },
    responsive: {
      type: "boolean",
      "default": false
    },
    previewable: {
      type: "boolean",
      "default": true
    }
  },
  supports: {
    align: true,
    reusable: false,
    html: false
  }
};
var EMBED_BLOCK = metadata.name;
/**
 * Default transforms for generic embeds.
 */

var transforms = {
  from: [{
    type: 'raw',
    isMatch: function isMatch(node) {
      return node.nodeName === 'P' && /^\s*(https?:\/\/\S+)\s*$/i.test(node.textContent);
    },
    transform: function transform(node) {
      return createBlock(EMBED_BLOCK, {
        url: node.textContent.trim()
      });
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(_ref) {
      var url = _ref.url,
          caption = _ref.caption;
      var link = createElement("a", {
        href: url
      }, caption || url);
      return createBlock('core/paragraph', {
        content: renderToString(link)
      });
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map