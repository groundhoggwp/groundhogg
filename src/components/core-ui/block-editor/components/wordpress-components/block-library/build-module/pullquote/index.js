/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { pullquote as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { SOLID_COLOR_STYLE_NAME } from './shared';
import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/pullquote",
  category: "text",
  attributes: {
    value: {
      type: "string",
      source: "html",
      selector: "blockquote",
      multiline: "p"
    },
    citation: {
      type: "string",
      source: "html",
      selector: "cite",
      "default": ""
    },
    mainColor: {
      type: "string"
    },
    customMainColor: {
      type: "string"
    },
    textColor: {
      type: "string"
    },
    customTextColor: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    align: ["left", "right", "wide", "full"]
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Pullquote'),
  description: __('Give special visual emphasis to a quote from your text.'),
  icon: icon,
  example: {
    attributes: {
      value: '<p>' + // translators: Quote serving as example for the Pullquote block. Attributed to Matt Mullenweg.
      __('One of the hardest things to do in technology is disrupt yourself.') + '</p>',
      citation: __('Matt Mullenweg')
    }
  },
  styles: [{
    name: 'default',
    label: _x('Default', 'block style'),
    isDefault: true
  }, {
    name: SOLID_COLOR_STYLE_NAME,
    label: __('Solid color')
  }],
  transforms: transforms,
  edit: edit,
  save: save,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map