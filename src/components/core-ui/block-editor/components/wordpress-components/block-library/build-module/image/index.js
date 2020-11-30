/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { image as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/image",
  category: "media",
  attributes: {
    align: {
      type: "string"
    },
    url: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "src"
    },
    alt: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "alt",
      "default": ""
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    title: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "title"
    },
    href: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "href"
    },
    rel: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "rel"
    },
    linkClass: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "class"
    },
    id: {
      type: "number"
    },
    width: {
      type: "number"
    },
    height: {
      type: "number"
    },
    sizeSlug: {
      type: "string"
    },
    linkDestination: {
      type: "string"
    },
    linkTarget: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "target"
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
  title: __('Image'),
  description: __('Insert an image to make a visual statement.'),
  icon: icon,
  keywords: ['img', // "img" is not translated as it is intended to reflect the HTML <img> tag.
  __('photo'), __('picture')],
  example: {
    attributes: {
      sizeSlug: 'large',
      url: 'https://s.w.org/images/core/5.3/MtBlanc1.jpg',
      // translators: Caption accompanying an image of the Mont Blanc, which serves as an example for the Image block.
      caption: __('Mont Blanc appears—still, snowy, and serene.')
    }
  },
  styles: [{
    name: 'default',
    label: _x('Default', 'block style'),
    isDefault: true
  }, {
    name: 'rounded',
    label: _x('Rounded', 'block style')
  }],
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      var caption = attributes.caption,
          alt = attributes.alt,
          url = attributes.url;

      if (!url) {
        return __('Empty');
      }

      if (!alt) {
        return caption || '';
      } // This is intended to be read by a screen reader.
      // A period simply means a pause, no need to translate it.


      return alt + (caption ? '. ' + caption : '');
    }
  },
  getEditWrapperProps: function getEditWrapperProps(attributes) {
    return {
      'data-align': attributes.align
    };
  },
  transforms: transforms,
  edit: edit,
  save: save,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map