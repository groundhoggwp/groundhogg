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

var _transforms = _interopRequireDefault(require("./transforms"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/separator",
  category: "design",
  attributes: {
    color: {
      type: "string"
    },
    customColor: {
      type: "string"
    }
  },
  supports: {
    anchor: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Separator'),
  description: (0, _i18n.__)('Create a break between ideas or sections with a horizontal separator.'),
  icon: _icons.separator,
  keywords: [(0, _i18n.__)('horizontal-line'), 'hr', (0, _i18n.__)('divider')],
  example: {
    attributes: {
      customColor: '#065174',
      className: 'is-style-wide'
    }
  },
  styles: [{
    name: 'default',
    label: (0, _i18n.__)('Default'),
    isDefault: true
  }, {
    name: 'wide',
    label: (0, _i18n.__)('Wide Line')
  }, {
    name: 'dots',
    label: (0, _i18n.__)('Dots')
  }],
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map