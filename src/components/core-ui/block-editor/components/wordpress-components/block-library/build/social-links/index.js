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

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/social-links",
  category: "widgets",
  attributes: {
    openInNewTab: {
      type: "boolean",
      "default": false
    }
  },
  providesContext: {
    openInNewTab: "openInNewTab"
  },
  supports: {
    align: ["left", "center", "right"],
    lightBlockWrapper: true,
    anchor: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Social Icons'),
  description: (0, _i18n.__)('Display icons linking to your social media profiles or websites.'),
  keywords: [(0, _i18n._x)('links', 'block keywords')],
  example: {
    innerBlocks: [{
      name: 'core/social-link',
      attributes: {
        service: 'wordpress',
        url: 'https://wordpress.org'
      }
    }, {
      name: 'core/social-link',
      attributes: {
        service: 'facebook',
        url: 'https://www.facebook.com/WordPress/'
      }
    }, {
      name: 'core/social-link',
      attributes: {
        service: 'twitter',
        url: 'https://twitter.com/WordPress'
      }
    }]
  },
  styles: [{
    name: 'default',
    label: (0, _i18n.__)('Default'),
    isDefault: true
  }, {
    name: 'logos-only',
    label: (0, _i18n.__)('Logos Only')
  }, {
    name: 'pill-shape',
    label: (0, _i18n.__)('Pill Shape')
  }],
  icon: _icons.share,
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map