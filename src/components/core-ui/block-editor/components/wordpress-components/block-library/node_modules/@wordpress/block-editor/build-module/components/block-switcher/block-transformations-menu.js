import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { getBlockMenuDefaultClassName } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';

var BlockTransformationsMenu = function BlockTransformationsMenu(_ref) {
  var className = _ref.className,
      possibleBlockTransformations = _ref.possibleBlockTransformations,
      onSelect = _ref.onSelect;
  return createElement(MenuGroup, {
    label: __('Transform to'),
    className: className
  }, possibleBlockTransformations.map(function (item) {
    var name = item.name,
        icon = item.icon,
        title = item.title;
    return createElement(MenuItem, {
      key: name,
      className: getBlockMenuDefaultClassName(name),
      onClick: function onClick(event) {
        event.preventDefault();
        onSelect(name);
      }
    }, createElement(BlockIcon, {
      icon: icon,
      showColors: true
    }), title);
  }));
};

export default BlockTransformationsMenu;
//# sourceMappingURL=block-transformations-menu.js.map