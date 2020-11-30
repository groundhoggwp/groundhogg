"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _deprecated = _interopRequireDefault(require("./deprecated"));

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

var _transforms = _interopRequireDefault(require("./transforms"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/table",
  category: "text",
  attributes: {
    hasFixedLayout: {
      type: "boolean",
      "default": false
    },
    backgroundColor: {
      type: "string"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption",
      "default": ""
    },
    head: {
      type: "array",
      "default": [],
      source: "query",
      selector: "thead tr",
      query: {
        cells: {
          type: "array",
          "default": [],
          source: "query",
          selector: "td,th",
          query: {
            content: {
              type: "string",
              source: "html"
            },
            tag: {
              type: "string",
              "default": "td",
              source: "tag"
            },
            scope: {
              type: "string",
              source: "attribute",
              attribute: "scope"
            },
            align: {
              type: "string",
              source: "attribute",
              attribute: "data-align"
            }
          }
        }
      }
    },
    body: {
      type: "array",
      "default": [],
      source: "query",
      selector: "tbody tr",
      query: {
        cells: {
          type: "array",
          "default": [],
          source: "query",
          selector: "td,th",
          query: {
            content: {
              type: "string",
              source: "html"
            },
            tag: {
              type: "string",
              "default": "td",
              source: "tag"
            },
            scope: {
              type: "string",
              source: "attribute",
              attribute: "scope"
            },
            align: {
              type: "string",
              source: "attribute",
              attribute: "data-align"
            }
          }
        }
      }
    },
    foot: {
      type: "array",
      "default": [],
      source: "query",
      selector: "tfoot tr",
      query: {
        cells: {
          type: "array",
          "default": [],
          source: "query",
          selector: "td,th",
          query: {
            content: {
              type: "string",
              source: "html"
            },
            tag: {
              type: "string",
              "default": "td",
              source: "tag"
            },
            scope: {
              type: "string",
              source: "attribute",
              attribute: "scope"
            },
            align: {
              type: "string",
              source: "attribute",
              attribute: "data-align"
            }
          }
        }
      }
    }
  },
  supports: {
    anchor: true,
    align: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Table'),
  description: (0, _i18n.__)('Insert a table — perfect for sharing charts and data.'),
  icon: _icons.blockTable,
  example: {
    attributes: {
      head: [{
        cells: [{
          content: (0, _i18n.__)('Version'),
          tag: 'th'
        }, {
          content: (0, _i18n.__)('Jazz Musician'),
          tag: 'th'
        }, {
          content: (0, _i18n.__)('Release Date'),
          tag: 'th'
        }]
      }],
      body: [{
        cells: [{
          content: '5.2',
          tag: 'td'
        }, {
          content: 'Jaco Pastorius',
          tag: 'td'
        }, {
          content: (0, _i18n.__)('May 7, 2019'),
          tag: 'td'
        }]
      }, {
        cells: [{
          content: '5.1',
          tag: 'td'
        }, {
          content: 'Betty Carter',
          tag: 'td'
        }, {
          content: (0, _i18n.__)('February 21, 2019'),
          tag: 'td'
        }]
      }, {
        cells: [{
          content: '5.0',
          tag: 'td'
        }, {
          content: 'Bebo Valdés',
          tag: 'td'
        }, {
          content: (0, _i18n.__)('December 6, 2018'),
          tag: 'td'
        }]
      }]
    }
  },
  styles: [{
    name: 'regular',
    label: (0, _i18n._x)('Default', 'block style'),
    isDefault: true
  }, {
    name: 'stripes',
    label: (0, _i18n.__)('Stripes')
  }],
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map