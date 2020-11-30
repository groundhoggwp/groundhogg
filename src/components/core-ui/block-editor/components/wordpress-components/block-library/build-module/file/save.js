import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var href = attributes.href,
      fileName = attributes.fileName,
      textLinkHref = attributes.textLinkHref,
      textLinkTarget = attributes.textLinkTarget,
      showDownloadButton = attributes.showDownloadButton,
      downloadButtonText = attributes.downloadButtonText;
  return href && createElement("div", null, !RichText.isEmpty(fileName) && createElement("a", {
    href: textLinkHref,
    target: textLinkTarget,
    rel: textLinkTarget ? 'noreferrer noopener' : false
  }, createElement(RichText.Content, {
    value: fileName
  })), showDownloadButton && createElement("a", {
    href: href,
    className: "wp-block-file__button",
    download: true
  }, createElement(RichText.Content, {
    value: downloadButtonText
  })));
}
//# sourceMappingURL=save.js.map