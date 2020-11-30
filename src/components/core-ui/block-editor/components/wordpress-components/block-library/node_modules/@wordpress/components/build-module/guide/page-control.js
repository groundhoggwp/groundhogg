import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { times } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import Button from '../button';
import { PageControlIcon } from './icons';
export default function PageControl(_ref) {
  var currentPage = _ref.currentPage,
      numberOfPages = _ref.numberOfPages,
      setCurrentPage = _ref.setCurrentPage;
  return createElement("ul", {
    className: "components-guide__page-control",
    "aria-label": __('Guide controls')
  }, times(numberOfPages, function (page) {
    return createElement("li", {
      key: page // Set aria-current="step" on the active page, see https://www.w3.org/TR/wai-aria-1.1/#aria-current
      ,
      "aria-current": page === currentPage ? 'step' : undefined
    }, createElement(Button, {
      key: page,
      icon: createElement(PageControlIcon, {
        isSelected: page === currentPage
      }),
      "aria-label": sprintf(
      /* translators: 1: current page number 2: total number of pages */
      __('Page %1$d of %2$d'), page + 1, numberOfPages),
      onClick: function onClick() {
        return setCurrentPage(page);
      }
    }));
  }));
}
//# sourceMappingURL=page-control.js.map