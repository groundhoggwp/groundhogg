/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/text-columns",
  icon: "columns",
  category: "design",
  attributes: {
    content: {
      type: "array",
      source: "query",
      selector: "p",
      query: {
        children: {
          type: "string",
          source: "html"
        }
      },
      "default": [{}, {}]
    },
    columns: {
      type: "number",
      "default": 2
    },
    width: {
      type: "string"
    }
  },
  supports: {
    inserter: false
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Text Columns (deprecated)'),
  description: __('This block is deprecated. Please use the Columns block instead.'),
  transforms: transforms,
  getEditWrapperProps: function getEditWrapperProps(attributes) {
    var width = attributes.width;

    if ('wide' === width || 'full' === width) {
      return {
        'data-align': width
      };
    }
  },
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map