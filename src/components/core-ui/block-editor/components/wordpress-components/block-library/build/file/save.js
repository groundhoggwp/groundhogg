"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var href = attributes.href,
      fileName = attributes.fileName,
      textLinkHref = attributes.textLinkHref,
      textLinkTarget = attributes.textLinkTarget,
      showDownloadButton = attributes.showDownloadButton,
      downloadButtonText = attributes.downloadButtonText;
  return href && (0, _element.createElement)("div", null, !_blockEditor.RichText.isEmpty(fileName) && (0, _element.createElement)("a", {
    href: textLinkHref,
    target: textLinkTarget,
    rel: textLinkTarget ? 'noreferrer noopener' : false
  }, (0, _element.createElement)(_blockEditor.RichText.Content, {
    value: fileName
  })), showDownloadButton && (0, _element.createElement)("a", {
    href: href,
    className: "wp-block-file__button",
    download: true
  }, (0, _element.createElement)(_blockEditor.RichText.Content, {
    value: downloadButtonText
  })));
}
//# sourceMappingURL=save.js.map