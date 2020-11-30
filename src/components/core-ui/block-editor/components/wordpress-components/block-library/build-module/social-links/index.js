/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { share as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/social-links",
  category: "widgets",
  attributes: {
    openInNewTab: {
      type: "boolean",
      "default": false
    }
  },
  providesContext: {
    openInNewTab: "openInNewTab"
  },
  supports: {
    align: ["left", "center", "right"],
    lightBlockWrapper: true,
    anchor: true
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Social Icons'),
  description: __('Display icons linking to your social media profiles or websites.'),
  keywords: [_x('links', 'block keywords')],
  example: {
    innerBlocks: [{
      name: 'core/social-link',
      attributes: {
        service: 'wordpress',
        url: 'https://wordpress.org'
      }
    }, {
      name: 'core/social-link',
      attributes: {
        service: 'facebook',
        url: 'https://www.facebook.com/WordPress/'
      }
    }, {
      name: 'core/social-link',
      attributes: {
        service: 'twitter',
        url: 'https://twitter.com/WordPress'
      }
    }]
  },
  styles: [{
    name: 'default',
    label: __('Default'),
    isDefault: true
  }, {
    name: 'logos-only',
    label: __('Logos Only')
  }, {
    name: 'pill-shape',
    label: __('Pill Shape')
  }],
  icon: icon,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map