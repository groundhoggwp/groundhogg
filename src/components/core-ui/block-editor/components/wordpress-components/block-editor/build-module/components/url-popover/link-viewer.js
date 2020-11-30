import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { edit } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import LinkViewerURL from './link-viewer-url';
export default function LinkViewer(_ref) {
  var className = _ref.className,
      linkClassName = _ref.linkClassName,
      onEditLinkClick = _ref.onEditLinkClick,
      url = _ref.url,
      urlLabel = _ref.urlLabel,
      props = _objectWithoutProperties(_ref, ["className", "linkClassName", "onEditLinkClick", "url", "urlLabel"]);

  return createElement("div", _extends({
    className: classnames('block-editor-url-popover__link-viewer', className)
  }, props), createElement(LinkViewerURL, {
    url: url,
    urlLabel: urlLabel,
    className: linkClassName
  }), onEditLinkClick && createElement(Button, {
    icon: edit,
    label: __('Edit'),
    onClick: onEditLinkClick
  }));
}
//# sourceMappingURL=link-viewer.js.map