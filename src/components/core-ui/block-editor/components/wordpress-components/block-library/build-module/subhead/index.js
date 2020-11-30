import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SVG, Path } from '@wordpress/components';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/subhead",
  category: "text",
  attributes: {
    align: {
      type: "string"
    },
    content: {
      type: "string",
      source: "html",
      selector: "p"
    }
  },
  supports: {
    inserter: false,
    multiple: false
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Subheading (deprecated)'),
  description: __('This block is deprecated. Please use the Paragraph block instead.'),
  icon: createElement(SVG, {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 24 24"
  }, createElement(Path, {
    d: "M7.1 6l-.5 3h4.5L9.4 19h3l1.8-10h4.5l.5-3H7.1z"
  })),
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map