import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { createBlock, getBlockAttributes } from '@wordpress/blocks';
import { __UNSTABLE_LINE_SEPARATOR, create, join, replace, split, toHTMLString } from '@wordpress/rich-text';

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
      return createBlock('core/list', {
        values: toHTMLString({
          value: join(blockAttributes.map(function (_ref2) {
            var content = _ref2.content;
            var value = create({
              html: content
            });

            if (blockAttributes.length > 1) {
              return value;
            } // When converting only one block, transform
            // every line to a list item.


            return replace(value, /\n/g, __UNSTABLE_LINE_SEPARATOR);
          }), __UNSTABLE_LINE_SEPARATOR),
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
      return createBlock('core/list', {
        values: toHTMLString({
          value: create({
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

      return createBlock('core/list', _objectSpread(_objectSpread({}, getBlockAttributes('core/list', node.outerHTML)), attributes));
    }
  }].concat(_toConsumableArray(['*', '-'].map(function (prefix) {
    return {
      type: 'prefix',
      prefix: prefix,
      transform: function transform(content) {
        return createBlock('core/list', {
          values: "<li>".concat(content, "</li>")
        });
      }
    };
  })), _toConsumableArray(['1.', '1)'].map(function (prefix) {
    return {
      type: 'prefix',
      prefix: prefix,
      transform: function transform(content) {
        return createBlock('core/list', {
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
      return split(create({
        html: values,
        multilineTag: 'li',
        multilineWrapperTags: ['ul', 'ol']
      }), __UNSTABLE_LINE_SEPARATOR).map(function (piece) {
        return createBlock('core/paragraph', {
          content: toHTMLString({
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
      return split(create({
        html: values,
        multilineTag: 'li',
        multilineWrapperTags: ['ul', 'ol']
      }), __UNSTABLE_LINE_SEPARATOR).map(function (piece) {
        return createBlock('core/heading', {
          content: toHTMLString({
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
      return createBlock('core/quote', {
        value: toHTMLString({
          value: create({
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
      return createBlock('core/pullquote', {
        value: toHTMLString({
          value: create({
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
export default transforms;
//# sourceMappingURL=transforms.js.map