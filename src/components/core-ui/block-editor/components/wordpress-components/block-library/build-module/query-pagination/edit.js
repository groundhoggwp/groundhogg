import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Button, ButtonGroup } from '@wordpress/components';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { useQueryContext } from '../query';
export default function QueryPaginationEdit(_ref) {
  var _ref$context = _ref.context,
      _ref$context$query$pa = _ref$context.query.pages,
      pages = _ref$context$query$pa === void 0 ? 1 : _ref$context$query$pa,
      queryContext = _ref$context.queryContext;

  var _ref2 = useQueryContext() || queryContext,
      _ref3 = _slicedToArray(_ref2, 2),
      page = _ref3[0].page,
      setQueryContext = _ref3[1];

  var previous;

  if (page > 1) {
    previous = createElement(Button, {
      isPrimary: true,
      icon: chevronLeft,
      onClick: function onClick() {
        return setQueryContext({
          page: page - 1
        });
      }
    }, __('Previous'));
  }

  var next;

  if (page < pages) {
    next = createElement(Button, {
      isPrimary: true,
      icon: chevronRight,
      onClick: function onClick() {
        return setQueryContext({
          page: page + 1
        });
      }
    }, __('Next'));
  }

  return previous || next ? createElement(ButtonGroup, null, previous, next) : __('No pages to paginate.');
}
//# sourceMappingURL=edit.js.map