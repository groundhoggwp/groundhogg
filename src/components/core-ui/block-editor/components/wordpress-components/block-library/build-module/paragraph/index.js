/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { paragraph as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/paragraph",
  category: "text",
  attributes: {
    align: {
      type: "string"
    },
    content: {
      type: "string",
      source: "html",
      selector: "p",
      "default": ""
    },
    dropCap: {
      type: "boolean",
      "default": false
    },
    placeholder: {
      type: "string"
    },
    direction: {
      type: "string",
      "enum": ["ltr", "rtl"]
    }
  },
  supports: {
    anchor: true,
    className: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      linkColor: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true,
    __experimentalSelector: "p",
    __unstablePasteTextInline: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Paragraph'),
  description: __('Start with the building block of all narrative.'),
  icon: icon,
  keywords: [__('text')],
  example: {
    attributes: {
      content: __('In a village of La Mancha, the name of which I have no desire to call to mind, there lived not long since one of those gentlemen that keep a lance in the lance-rack, an old buckler, a lean hack, and a greyhound for coursing.'),
      style: {
        typography: {
          fontSize: 28
        }
      },
      dropCap: true
    }
  },
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      var content = attributes.content;
      return isEmpty(content) ? __('Empty') : content;
    }
  },
  transforms: transforms,
  deprecated: deprecated,
  merge: function merge(attributes, attributesToMerge) {
    return {
      content: (attributes.content || '') + (attributesToMerge.content || '')
    };
  },
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map