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
  name: "core/preformatted",
  category: "text",
  attributes: {
    content: {
      type: "string",
      source: "html",
      selector: "pre",
      "default": "",
      __unstablePreserveWhiteSpace: true
    }
  },
  supports: {
    anchor: true,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Preformatted'),
  description: (0, _i18n.__)('Add text that respects your spacing and tabs, and also allows styling.'),
  icon: _icons.preformatted,
  example: {
    attributes: {
      /* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
      // translators: Sample content for the Preformatted block. Can be replaced with a more locale-adequate work.
      content: (0, _i18n.__)('EXT. XANADU - FAINT DAWN - 1940 (MINIATURE)\nWindow, very small in the distance, illuminated.\nAll around this is an almost totally black screen. Now, as the camera moves slowly towards the window which is almost a postage stamp in the frame, other forms appear;')
      /* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

    }
  },
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default,
  merge: function merge(attributes, attributesToMerge) {
    return {
      content: attributes.content + attributesToMerge.content
    };
  }
};
exports.settings = settings;
//# sourceMappingURL=index.js.map