/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { classic as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/freeform",
  category: "text",
  attributes: {
    content: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    className: false,
    customClassName: false,
    reusable: false
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: _x('Classic', 'block title'),
  description: __('Use the classic WordPress editor.'),
  icon: icon,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map