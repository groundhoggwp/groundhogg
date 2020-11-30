import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { create, join, split, toHTMLString } from '@wordpress/rich-text';
var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return createBlock('core/pullquote', {
        value: toHTMLString({
          value: join(attributes.map(function (_ref) {
            var content = _ref.content;
            return create({
              html: content
            });
          }), "\u2028"),
          multilineTag: 'p'
        }),
        anchor: attributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/heading'],
    transform: function transform(_ref2) {
      var content = _ref2.content,
          anchor = _ref2.anchor;
      return createBlock('core/pullquote', {
        value: "<p>".concat(content, "</p>"),
        anchor: anchor
      });
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(_ref3) {
      var value = _ref3.value,
          citation = _ref3.citation;
      var paragraphs = [];

      if (value && value !== '<p></p>') {
        paragraphs.push.apply(paragraphs, _toConsumableArray(split(create({
          html: value,
          multilineTag: 'p'
        }), "\u2028").map(function (piece) {
          return createBlock('core/paragraph', {
            content: toHTMLString({
              value: piece
            })
          });
        })));
      }

      if (citation && citation !== '<p></p>') {
        paragraphs.push(createBlock('core/paragraph', {
          content: citation
        }));
      }

      if (paragraphs.length === 0) {
        return createBlock('core/paragraph', {
          content: ''
        });
      }

      return paragraphs;
    }
  }, {
    type: 'block',
    blocks: ['core/heading'],
    transform: function transform(_ref4) {
      var value = _ref4.value,
          citation = _ref4.citation,
          attrs = _objectWithoutProperties(_ref4, ["value", "citation"]);

      // If there is no pullquote content, use the citation as the
      // content of the resulting heading. A nonexistent citation
      // will result in an empty heading.
      if (value === '<p></p>') {
        return createBlock('core/heading', {
          content: citation
        });
      }

      var pieces = split(create({
        html: value,
        multilineTag: 'p'
      }), "\u2028");
      var headingBlock = createBlock('core/heading', {
        content: toHTMLString({
          value: pieces[0]
        })
      });

      if (!citation && pieces.length === 1) {
        return headingBlock;
      }

      var quotePieces = pieces.slice(1);
      var pullquoteBlock = createBlock('core/pullquote', _objectSpread(_objectSpread({}, attrs), {}, {
        citation: citation,
        value: toHTMLString({
          value: quotePieces.length ? join(pieces.slice(1), "\u2028") : create(),
          multilineTag: 'p'
        })
      }));
      return [headingBlock, pullquoteBlock];
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map