"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var POPOVER_PROPS = {
  position: 'bottom right',
  isAlternate: true
};

var FormatToolbar = function FormatToolbar() {
  return (0, _element.createElement)("div", {
    className: "block-editor-format-toolbar"
  }, (0, _element.createElement)(_components.ToolbarGroup, null, ['bold', 'italic', 'link', 'text-color'].map(function (format) {
    return (0, _element.createElement)(_components.Slot, {
      name: "RichText.ToolbarControls.".concat(format),
      key: format
    });
  }), (0, _element.createElement)(_components.Slot, {
    name: "RichText.ToolbarControls"
  }, function (fills) {
    return fills.length !== 0 && (0, _element.createElement)(_components.ToolbarItem, null, function (toggleProps) {
      return (0, _element.createElement)(_components.DropdownMenu, {
        icon: _icons.chevronDown,
        label: (0, _i18n.__)('More rich text controls'),
        toggleProps: toggleProps,
        controls: (0, _lodash.orderBy)(fills.map(function (_ref) {
          var _ref2 = (0, _slicedToArray2.default)(_ref, 1),
              props = _ref2[0].props;

          return props;
        }), 'title'),
        popoverProps: POPOVER_PROPS
      });
    });
  })));
};

var _default = FormatToolbar;
exports.default = _default;
//# sourceMappingURL=index.js.map