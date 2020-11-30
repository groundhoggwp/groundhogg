/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { file as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import edit from './edit';
var metadata = {
  name: "core/file",
  category: "media",
  attributes: {
    id: {
      type: "number"
    },
    href: {
      type: "string"
    },
    fileName: {
      type: "string",
      source: "html",
      selector: "a:not([download])"
    },
    textLinkHref: {
      type: "string",
      source: "attribute",
      selector: "a:not([download])",
      attribute: "href"
    },
    textLinkTarget: {
      type: "string",
      source: "attribute",
      selector: "a:not([download])",
      attribute: "target"
    },
    showDownloadButton: {
      type: "boolean",
      "default": true
    },
    downloadButtonText: {
      type: "string",
      source: "html",
      selector: "a[download]"
    }
  },
  supports: {
    anchor: true,
    align: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('File'),
  description: __('Add a link to a downloadable file.'),
  icon: icon,
  keywords: [__('document'), __('pdf'), __('download')],
  transforms: transforms,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map