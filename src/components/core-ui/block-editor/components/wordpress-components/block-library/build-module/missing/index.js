/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { getBlockType } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/missing",
  category: "text",
  attributes: {
    originalName: {
      type: "string"
    },
    originalUndelimitedContent: {
      type: "string"
    },
    originalContent: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    className: false,
    customClassName: false,
    inserter: false,
    html: false,
    reusable: false
  }
};
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  name: name,
  title: __('Unsupported'),
  description: __('Your site doesn’t include support for this block.'),
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      var originalName = attributes.originalName;
      var originalBlockType = originalName ? getBlockType(originalName) : undefined;

      if (originalBlockType) {
        return originalBlockType.settings.title || originalName;
      }

      return '';
    }
  },
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map