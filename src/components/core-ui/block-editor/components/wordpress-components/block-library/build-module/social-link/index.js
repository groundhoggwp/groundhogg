/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { share as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/social-link",
  category: "widgets",
  parent: ["core/social-links"],
  attributes: {
    url: {
      type: "string"
    },
    service: {
      type: "string"
    },
    label: {
      type: "string"
    }
  },
  usesContext: ["openInNewTab"],
  supports: {
    reusable: false,
    html: false,
    lightBlockWrapper: true
  }
};
import variations from './variations';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Social Icon'),
  icon: icon,
  edit: edit,
  description: __('Display an icon linking to a social media profile or website.'),
  variations: variations
};
//# sourceMappingURL=index.js.map