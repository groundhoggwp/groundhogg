"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkPreview;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _url = require("@wordpress/url");

var _viewerSlot = require("./viewer-slot");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LinkPreview(_ref) {
  var value = _ref.value,
      onEditClick = _ref.onEditClick;
  var displayURL = value && (0, _url.filterURLForDisplay)((0, _url.safeDecodeURI)(value.url)) || '';
  return (0, _element.createElement)("div", {
    "aria-label": (0, _i18n.__)('Currently selected'),
    "aria-selected": "true",
    className: (0, _classnames.default)('block-editor-link-control__search-item', {
      'is-current': true
    })
  }, (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-header"
  }, (0, _element.createElement)(_components.ExternalLink, {
    className: "block-editor-link-control__search-item-title",
    href: value.url
  }, value && value.title || displayURL), value && value.title && (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-info"
  }, displayURL)), (0, _element.createElement)(_components.Button, {
    isSecondary: true,
    onClick: function onClick() {
      return onEditClick();
    },
    className: "block-editor-link-control__search-item-action"
  }, (0, _i18n.__)('Edit')), (0, _element.createElement)(_viewerSlot.ViewerSlot, {
    fillProps: value
  }));
}
//# sourceMappingURL=link-preview.js.map