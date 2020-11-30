"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

var _deprecated = _interopRequireDefault(require("./deprecated"));

/**
 * WordPress dependencies
 */

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
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Navigation'),
  icon: _icons.navigation,
  description: (0, _i18n.__)('A collection of blocks that allow visitors to get around your site.'),
  keywords: [(0, _i18n.__)('menu'), (0, _i18n.__)('navigation'), (0, _i18n.__)('links')],
  variations: [{
    name: 'horizontal',
    isDefault: true,
    title: (0, _i18n.__)('Navigation (horizontal)'),
    description: (0, _i18n.__)('Links shown in a row.'),
    attributes: {
      orientation: 'horizontal'
    }
  }, {
    name: 'vertical',
    title: (0, _i18n.__)('Navigation (vertical)'),
    description: (0, _i18n.__)('Links shown in a column.'),
    attributes: {
      orientation: 'vertical'
    }
  }],
  example: {
    innerBlocks: [{
      name: 'core/navigation-link',
      attributes: {
        // translators: 'Home' as in a website's home page.
        label: (0, _i18n.__)('Home'),
        url: 'https://make.wordpress.org/'
      }
    }, {
      name: 'core/navigation-link',
      attributes: {
        // translators: 'About' as in a website's about page.
        label: (0, _i18n.__)('About'),
        url: 'https://make.wordpress.org/'
      }
    }, {
      name: 'core/navigation-link',
      attributes: {
        // translators: 'Contact' as in a website's contact page.
        label: (0, _i18n.__)('Contact'),
        url: 'https://make.wordpress.org/'
      }
    }]
  },
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map