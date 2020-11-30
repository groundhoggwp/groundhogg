"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _blocks = require("@wordpress/blocks");

var _richText = require("@wordpress/rich-text");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/paragraph'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/pullquote', {
        value: (0, _richText.toHTMLString)({
          value: (0, _richText.join)(attributes.map(function (_ref) {
            var content = _ref.content;
            return (0, _richText.create)({
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
      return (0, _blocks.createBlock)('core/pullquote', {
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
        paragraphs.push.apply(paragraphs, (0, _toConsumableArray2.default)((0, _richText.split)((0, _richText.create)({
          html: value,
          multilineTag: 'p'
        }), "\u2028").map(function (piece) {
          return (0, _blocks.createBlock)('core/paragraph', {
            content: (0, _richText.toHTMLString)({
              value: piece
            })
          });
        })));
      }

      if (citation && citation !== '<p></p>') {
        paragraphs.push((0, _blocks.createBlock)('core/paragraph', {
          content: citation
        }));
      }

      if (paragraphs.length === 0) {
        return (0, _blocks.createBlock)('core/paragraph', {
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
          attrs = (0, _objectWithoutProperties2.default)(_ref4, ["value", "citation"]);

      // If there is no pullquote content, use the citation as the
      // content of the resulting heading. A nonexistent citation
      // will result in an empty heading.
      if (value === '<p></p>') {
        return (0, _blocks.createBlock)('core/heading', {
          content: citation
        });
      }

      var pieces = (0, _richText.split)((0, _richText.create)({
        html: value,
        multilineTag: 'p'
      }), "\u2028");
      var headingBlock = (0, _blocks.createBlock)('core/heading', {
        content: (0, _richText.toHTMLString)({
          value: pieces[0]
        })
      });

      if (!citation && pieces.length === 1) {
        return headingBlock;
      }

      var quotePieces = pieces.slice(1);
      var pullquoteBlock = (0, _blocks.createBlock)('core/pullquote', _objectSpread(_objectSpread({}, attrs), {}, {
        citation: citation,
        value: (0, _richText.toHTMLString)({
          value: quotePieces.length ? (0, _richText.join)(pieces.slice(1), "\u2028") : (0, _richText.create)(),
          multilineTag: 'p'
        })
      }));
      return [headingBlock, pullquoteBlock];
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map