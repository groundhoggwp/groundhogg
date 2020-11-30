/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { blockTable as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
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
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Table'),
  description: __('Insert a table — perfect for sharing charts and data.'),
  icon: icon,
  example: {
    attributes: {
      head: [{
        cells: [{
          content: __('Version'),
          tag: 'th'
        }, {
          content: __('Jazz Musician'),
          tag: 'th'
        }, {
          content: __('Release Date'),
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
          content: __('May 7, 2019'),
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
          content: __('February 21, 2019'),
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
          content: __('December 6, 2018'),
          tag: 'td'
        }]
      }]
    }
  },
  styles: [{
    name: 'regular',
    label: _x('Default', 'block style'),
    isDefault: true
  }, {
    name: 'stripes',
    label: __('Stripes')
  }],
  transforms: transforms,
  edit: edit,
  save: save,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map