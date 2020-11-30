/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { pageBreak as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/nextpage",
  category: "design",
  parent: ["core/post-content"],
  supports: {
    customClassName: false,
    className: false,
    html: false
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Page Break'),
  description: __('Separate your content into a multi-page experience.'),
  icon: icon,
  keywords: [__('next page'), __('pagination')],
  example: {},
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map