/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { navigation as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/navigation",
  category: "design",
  attributes: {
    orientation: {
      type: "string"
    },
    textColor: {
      type: "string"
    },
    customTextColor: {
      type: "string"
    },
    rgbTextColor: {
      type: "string"
    },
    backgroundColor: {
      type: "string"
    },
    customBackgroundColor: {
      type: "string"
    },
    rgbBackgroundColor: {
      type: "string"
    },
    itemsJustification: {
      type: "string"
    },
    showSubmenuIcon: {
      type: "boolean",
      "default": true
    }
  },
  providesContext: {
    textColor: "textColor",
    customTextColor: "customTextColor",
    backgroundColor: "backgroundColor",
    customBackgroundColor: "customBackgroundColor",
    fontSize: "fontSize",
    customFontSize: "customFontSize",
    showSubmenuIcon: "showSubmenuIcon"
  },
  supports: {
    align: ["wide", "full"],
    anchor: true,
    html: false,
    inserter: true,
    lightBlockWrapper: true,
    __experimentalFontSize: true,
    __experimentalColor: {
      textColor: true,
      backgroundColor: true
    }
  }
};
import edit from './edit';
import save from './save';
import deprecated from './deprecated';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Navigation'),
  icon: icon,
  description: __('A collection of blocks that allow visitors to get around your site.'),
  keywords: [__('menu'), __('navigation'), __('links')],
  variations: [{
    name: 'horizontal',
    isDefault: true,
    title: __('Navigation (horizontal)'),
    description: __('Links shown in a row.'),
    attributes: {
      orientation: 'horizontal'
    }
  }, {
    name: 'vertical',
    title: __('Navigation (vertical)'),
    description: __('Links shown in a column.'),
    attributes: {
      orientation: 'vertical'
    }
  }],
  example: {
    innerBlocks: [{
      name: 'core/navigation-link',
      attributes: {
        // translators: 'Home' as in a website's home page.
        label: __('Home'),
        url: 'https://make.wordpress.org/'
      }
    }, {
      name: 'core/navigation-link',
      attributes: {
        // translators: 'About' as in a website's about page.
        label: __('About'),
        url: 'https://make.wordpress.org/'
      }
    }, {
      name: 'core/navigation-link',
      attributes: {
        // translators: 'Contact' as in a website's contact page.
        label: __('Contact'),
        url: 'https://make.wordpress.org/'
      }
    }]
  },
  edit: edit,
  save: save,
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map