"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _transforms = _interopRequireDefault(require("./transforms"));

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/buttons",
  category: "design",
  supports: {
    anchor: true,
    align: true,
    alignWide: false,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Buttons'),
  description: (0, _i18n.__)('Prompt visitors to take action with a group of button-style links.'),
  icon: _icons.button,
  keywords: [(0, _i18n.__)('link')],
  example: {
    innerBlocks: [{
      name: 'core/button',
      attributes: {
        text: (0, _i18n.__)('Find out more')
      }
    }, {
      name: 'core/button',
      attributes: {
        text: (0, _i18n.__)('Contact us')
      }
    }]
  },
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map