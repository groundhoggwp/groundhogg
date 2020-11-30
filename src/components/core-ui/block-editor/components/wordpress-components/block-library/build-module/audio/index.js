/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { audio as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/audio",
  category: "media",
  attributes: {
    src: {
      type: "string",
      source: "attribute",
      selector: "audio",
      attribute: "src"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    id: {
      type: "number"
    },
    autoplay: {
      type: "boolean",
      source: "attribute",
      selector: "audio",
      attribute: "autoplay"
    },
    loop: {
      type: "boolean",
      source: "attribute",
      selector: "audio",
      attribute: "loop"
    },
    preload: {
      type: "string",
      source: "attribute",
      selector: "audio",
      attribute: "preload"
    }
  },
  supports: {
    anchor: true,
    align: true,
    lightBlockWrapper: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Audio'),
  description: __('Embed a simple audio player.'),
  keywords: [__('music'), __('sound'), __('podcast'), __('recording')],
  icon: icon,
  transforms: transforms,
  deprecated: deprecated,
  edit: edit,
  save: save
};
//# sourceMappingURL=index.js.map