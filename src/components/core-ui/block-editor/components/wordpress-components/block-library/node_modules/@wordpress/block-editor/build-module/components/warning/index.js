import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Children } from '@wordpress/element';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { moreHorizontal } from '@wordpress/icons';

function Warning(_ref) {
  var className = _ref.className,
      actions = _ref.actions,
      children = _ref.children,
      secondaryActions = _ref.secondaryActions;
  return createElement("div", {
    className: classnames(className, 'block-editor-warning')
  }, createElement("div", {
    className: "block-editor-warning__contents"
  }, createElement("p", {
    className: "block-editor-warning__message"
  }, children), (Children.count(actions) > 0 || secondaryActions) && createElement("div", {
    className: "block-editor-warning__actions"
  }, Children.count(actions) > 0 && Children.map(actions, function (action, i) {
    return createElement("span", {
      key: i,
      className: "block-editor-warning__action"
    }, action);
  }), secondaryActions && createElement(DropdownMenu, {
    className: "block-editor-warning__secondary",
    icon: moreHorizontal,
    label: __('More options'),
    popoverProps: {
      position: 'bottom left',
      className: 'block-editor-warning__dropdown'
    },
    noIcons: true
  }, function () {
    return createElement(MenuGroup, null, secondaryActions.map(function (item, pos) {
      return createElement(MenuItem, {
        onClick: item.onClick,
        key: pos
      }, item.title);
    }));
  }))));
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/warning/README.md
 */


export default Warning;
//# sourceMappingURL=index.js.map