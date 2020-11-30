import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { ExternalLink } from '@wordpress/components';
import { safeDecodeURI, filterURLForDisplay } from '@wordpress/url';
export default function LinkViewerURL(_ref) {
  var url = _ref.url,
      urlLabel = _ref.urlLabel,
      className = _ref.className;
  var linkClassName = classnames(className, 'block-editor-url-popover__link-viewer-url');

  if (!url) {
    return createElement("span", {
      className: linkClassName
    });
  }

  return createElement(ExternalLink, {
    className: linkClassName,
    href: url
  }, urlLabel || filterURLForDisplay(safeDecodeURI(url)));
}
//# sourceMappingURL=link-viewer-url.js.map