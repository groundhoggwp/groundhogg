"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkViewer;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _linkViewerUrl = _interopRequireDefault(require("./link-viewer-url"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LinkViewer(_ref) {
  var className = _ref.className,
      linkClassName = _ref.linkClassName,
      onEditLinkClick = _ref.onEditLinkClick,
      url = _ref.url,
      urlLabel = _ref.urlLabel,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "linkClassName", "onEditLinkClick", "url", "urlLabel"]);
  return (0, _element.createElement)("div", (0, _extends2.default)({
    className: (0, _classnames.default)('block-editor-url-popover__link-viewer', className)
  }, props), (0, _element.createElement)(_linkViewerUrl.default, {
    url: url,
    urlLabel: urlLabel,
    className: linkClassName
  }), onEditLinkClick && (0, _element.createElement)(_components.Button, {
    icon: _icons.edit,
    label: (0, _i18n.__)('Edit'),
    onClick: onEditLinkClick
  }));
}
//# sourceMappingURL=link-viewer.js.map