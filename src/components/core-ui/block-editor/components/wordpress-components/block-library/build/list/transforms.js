"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _blocks = require("@wordpress/blocks");

var _richText = require("@wordpress/rich-text");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function getListContentSchema(_ref) {
  var phrasingContentSchema = _ref.phrasingContentSchema;

  var listContentSchema = _objectSpread(_objectSpread({}, phrasingContentSchema), {}, {
    ul: {},
    ol: {
      attributes: ['type', 'start', 'reversed']
    }
  }); // Recursion is needed.
  // Possible: ul > li > ul.
  // Impossible: ul > ul.


  ['ul', 'ol'].forEach(function (tag) {
    listContentSchema[tag].children = {
      li: {
        children: listContentSchema
      }
    };
  });
  return listContentSchema;
}

var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/paragraph', 'core/heading'],
    transform: function transform(blockAttributes) {
      return (0, _blocks.createBlock)('core/list', {
        values: (0, _richText.toHTMLString)({
          value: (0, _richText.join)(blockAttributes.map(function (_ref2) {
            var content = _ref2.content;
            var value = (0, _richText.create)({
              html: content
            });

            if (blockAttributes.length > 1) {
              return value;
            } // When converting only one block, transform
            // every line to a list item.


            return (0, _richText.replace)(value, /\n/g, _richText.__UNSTABLE_LINE_SEPARATOR);
          }), _richText.__UNSTABLE_LINE_SEPARATOR),
          multilineTag: 'li'
        }),
        anchor: blockAttributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/quote', 'core/pullquote'],
    transform: function transform(_ref3) {
      var value = _ref3.value,
          anchor = _ref3.anchor;
      return (0, _blocks.createBlock)('core/list', {
        values: (0, _richText.toHTMLString)({
          value: (0, _richText.create)({
            html: value,
            multilineTag: 'p'
          }),
          multilineTag: 'li'
        }),
        anchor: anchor
      });
    }
  }, {
    type: 'raw',
    selector: 'ol,ul',
    schema: function schema(args) {
      return {
        ol: getListContentSchema(args).ol,
        ul: getListContentSchema(args).ul
      };
    },
    transform: function transform(node) {
      var attributes = {
        ordered: node.nodeName === 'OL',
        anchor: node.id === '' ? undefined : node.id
      };

      if (attributes.ordered) {
        var type = node.getAttribute('type');

        if (type) {
          attributes.type = type;
        }

        if (node.getAttribute('reversed') !== null) {
          attributes.reversed = true;
        }

        var start = parseInt(node.getAttribute('start'), 10);

        if (!isNaN(start) && ( // start=1 only makes sense if the list is reversed.
        start !== 1 || attributes.reversed)) {
          attributes.start = start;
        }
      }

      return (0, _blocks.createBlock)('core/list', _objectSpread(_objectSpread({}, (0, _blocks.getBlockAttributes)('core/list', node.outerHTML)), attributes));
    }
  }].concat((0, _toConsumableArray2.default)(['*', '-'].map(function (prefix) {
    return {
      type: 'prefix',
      prefix: prefix,
      transform: function transform(content) {
        return (0, _blocks.createBlock)('core/list', {
          values: "<li>".concat(content, "</li>")
        });
      }
    };
  })), (0, _toConsumableArray2.default)(['1.', '1)'].map(function (prefix) {
    return {
      type: 'prefix',
      prefix: prefix,
      transform: function transform(content) {
        return (0, _blocks.createBlock)('core/list', {
          ordered: true,
          values: "<li>".concat(content, "</li>")
        });
      }
    };
  }))),
  to: [{
    type: 'block',
    blocks: ['core/paragraph'],
    transform: function transform(_ref4) {
      var values = _ref4.values;
      return (0, _richText.split)((0, _richText.create)({
        html: values,
        multilineTag: 'li',
        multilineWrapperTags: ['ul', 'ol']
      }), _richText.__UNSTABLE_LINE_SEPARATOR).map(function (piece) {
        return (0, _blocks.createBlock)('core/paragraph', {
          content: (0, _richText.toHTMLString)({
            value: piece
          })
        });
      });
    }
  }, {
    type: 'block',
    blocks: ['core/heading'],
    transform: function transform(_ref5) {
      var values = _ref5.values;
      return (0, _richText.split)((0, _richText.create)({
        html: values,
        multilineTag: 'li',
        multilineWrapperTags: ['ul', 'ol']
      }), _richText.__UNSTABLE_LINE_SEPARATOR).map(function (piece) {
        return (0, _blocks.createBlock)('core/heading', {
          content: (0, _richText.toHTMLString)({
            value: piece
          })
        });
      });
    }
  }, {
    type: 'block',
    blocks: ['core/quote'],
    transform: function transform(_ref6) {
      var values = _ref6.values,
          anchor = _ref6.anchor;
      return (0, _blocks.createBlock)('core/quote', {
        value: (0, _richText.toHTMLString)({
          value: (0, _richText.create)({
            html: values,
            multilineTag: 'li',
            multilineWrapperTags: ['ul', 'ol']
          }),
          multilineTag: 'p'
        }),
        anchor: anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/pullquote'],
    transform: function transform(_ref7) {
      var values = _ref7.values,
          anchor = _ref7.anchor;
      return (0, _blocks.createBlock)('core/pullquote', {
        value: (0, _richText.toHTMLString)({
          value: (0, _richText.create)({
            html: values,
            multilineTag: 'li',
            multilineWrapperTags: ['ul', 'ol']
          }),
          multilineTag: 'p'
        }),
        anchor: anchor
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map