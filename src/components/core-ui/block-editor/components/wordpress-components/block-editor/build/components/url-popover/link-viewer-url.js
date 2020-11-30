"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkViewerURL;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _url = require("@wordpress/url");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function LinkViewerURL(_ref) {
  var url = _ref.url,
      urlLabel = _ref.urlLabel,
      className = _ref.className;
  var linkClassName = (0, _classnames.default)(className, 'block-editor-url-popover__link-viewer-url');

  if (!url) {
    return (0, _element.createElement)("span", {
      className: linkClassName
    });
  }

  return (0, _element.createElement)(_components.ExternalLink, {
    className: linkClassName,
    href: url
  }, urlLabel || (0, _url.filterURLForDisplay)((0, _url.safeDecodeURI)(url)));
}
//# sourceMappingURL=link-viewer-url.js.map