/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { column as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/column",
  category: "text",
  parent: ["core/columns"],
  attributes: {
    verticalAlignment: {
      type: "string"
    },
    width: {
      type: "number",
      min: 0,
      max: 100
    }
  },
  supports: {
    anchor: true,
    reusable: false,
    html: false,
    lightBlockWrapper: true
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Column'),
  icon: icon,
  description: __('A single column within a columns block.'),
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map