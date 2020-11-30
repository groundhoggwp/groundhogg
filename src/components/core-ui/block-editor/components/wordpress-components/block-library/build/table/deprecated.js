"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var supports = {
  align: true
};
var deprecated = [{
  attributes: {
    hasFixedLayout: {
      type: 'boolean',
      default: false
    },
    backgroundColor: {
      type: 'string'
    },
    head: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'thead tr',
      query: {
        cells: {
          type: 'array',
          default: [],
          source: 'query',
          selector: 'td,th',
          query: {
            content: {
              type: 'string',
              source: 'html'
            },
            tag: {
              type: 'string',
              default: 'td',
              source: 'tag'
            },
            scope: {
              type: 'string',
              source: 'attribute',
              attribute: 'scope'
            }
          }
        }
      }
    },
    body: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'tbody tr',
      query: {
        cells: {
          type: 'array',
          default: [],
          source: 'query',
          selector: 'td,th',
          query: {
            content: {
              type: 'string',
              source: 'html'
            },
            tag: {
              type: 'string',
              default: 'td',
              source: 'tag'
            },
            scope: {
              type: 'string',
              source: 'attribute',
              attribute: 'scope'
            }
          }
        }
      }
    },
    foot: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'tfoot tr',
      query: {
        cells: {
          type: 'array',
          default: [],
          source: 'query',
          selector: 'td,th',
          query: {
            content: {
              type: 'string',
              source: 'html'
            },
            tag: {
              type: 'string',
              default: 'td',
              source: 'tag'
            },
            scope: {
              type: 'string',
              source: 'attribute',
              attribute: 'scope'
            }
          }
        }
      }
    }
  },
  supports: supports,
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var hasFixedLayout = attributes.hasFixedLayout,
        head = attributes.head,
        body = attributes.body,
        foot = attributes.foot,
        backgroundColor = attributes.backgroundColor;
    var isEmpty = !head.length && !body.length && !foot.length;

    if (isEmpty) {
      return null;
    }

    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var classes = (0, _classnames.default)(backgroundClass, {
      'has-fixed-layout': hasFixedLayout,
      'has-background': !!backgroundClass
    });

    var Section = function Section(_ref2) {
      var type = _ref2.type,
          rows = _ref2.rows;

      if (!rows.length) {
        return null;
      }

      var Tag = "t".concat(type);
      return (0, _element.createElement)(Tag, null, rows.map(function (_ref3, rowIndex) {
        var cells = _ref3.cells;
        return (0, _element.createElement)("tr", {
          key: rowIndex
        }, cells.map(function (_ref4, cellIndex) {
          var content = _ref4.content,
              tag = _ref4.tag,
              scope = _ref4.scope;
          return (0, _element.createElement)(_blockEditor.RichText.Content, {
            tagName: tag,
            value: content,
            key: cellIndex,
            scope: tag === 'th' ? scope : undefined
          });
        }));
      }));
    };

    return (0, _element.createElement)("table", {
      className: classes
    }, (0, _element.createElement)(Section, {
      type: "head",
      rows: head
    }), (0, _element.createElement)(Section, {
      type: "body",
      rows: body
    }), (0, _element.createElement)(Section, {
      type: "foot",
      rows: foot
    }));
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map