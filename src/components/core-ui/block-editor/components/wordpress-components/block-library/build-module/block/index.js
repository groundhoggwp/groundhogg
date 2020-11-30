/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/block",
  category: "reusable",
  attributes: {
    ref: {
      type: "number"
    }
  },
  supports: {
    customClassName: false,
    html: false,
    inserter: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Reusable Block'),
  description: __('Create and save content to reuse across your site. Update the block, and the changes apply everywhere it’s used.'),
  edit: edit
};
//# sourceMappingURL=index.js.map