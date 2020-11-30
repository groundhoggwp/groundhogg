import _extends from "@babel/runtime/helpers/esm/extends";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { noop, omit } from 'lodash';
/**
 * Internal dependencies
 */

import Notice from './';
/**
 * Renders a list of notices.
 *
 * @param  {Object}   $0           Props passed to the component.
 * @param  {Array}    $0.notices   Array of notices to render.
 * @param  {Function} $0.onRemove  Function called when a notice should be removed / dismissed.
 * @param  {Object}   $0.className Name of the class used by the component.
 * @param  {Object}   $0.children  Array of children to be rendered inside the notice list.
 * @return {Object}                The rendered notices list.
 */

function NoticeList(_ref) {
  var notices = _ref.notices,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? noop : _ref$onRemove,
      className = _ref.className,
      children = _ref.children;

  var removeNotice = function removeNotice(id) {
    return function () {
      return onRemove(id);
    };
  };

  className = classnames('components-notice-list', className);
  return createElement("div", {
    className: className
  }, children, _toConsumableArray(notices).reverse().map(function (notice) {
    return createElement(Notice, _extends({}, omit(notice, ['content']), {
      key: notice.id,
      onRemove: removeNotice(notice.id)
    }), notice.content);
  }));
}

export default NoticeList;
//# sourceMappingURL=list.js.map