/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { verse as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/verse",
  category: "text",
  attributes: {
    content: {
      type: "string",
      source: "html",
      selector: "pre",
      "default": "",
      __unstablePreserveWhiteSpace: true
    },
    textAlign: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    lightBlockWrapper: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Verse'),
  description: __('Insert poetry. Use special spacing formats. Or quote song lyrics.'),
  icon: icon,
  example: {
    attributes: {
      /* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
      // translators: Sample content for the Verse block. Can be replaced with a more locale-adequate work.
      content: __('WHAT was he doing, the great god Pan,\n	Down in the reeds by the river?\nSpreading ruin and scattering ban,\nSplashing and paddling with hoofs of a goat,\nAnd breaking the golden lilies afloat\n    With the dragon-fly on the river.')
      /* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

    }
  },
  keywords: [__('poetry'), __('poem')],
  transforms: transforms,
  deprecated: deprecated,
  merge: function merge(attributes, attributesToMerge) {
    return {
      content: attributes.content + attributesToMerge.content
    };
  },
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map