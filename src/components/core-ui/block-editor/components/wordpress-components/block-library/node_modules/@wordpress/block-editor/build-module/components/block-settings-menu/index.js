import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { ToolbarGroup, ToolbarItem } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockSettingsDropdown from './block-settings-dropdown';
export function BlockSettingsMenu(_ref) {
  var clientIds = _ref.clientIds,
      props = _objectWithoutProperties(_ref, ["clientIds"]);

  return createElement(ToolbarGroup, null, createElement(ToolbarItem, null, function (toggleProps) {
    return createElement(BlockSettingsDropdown, _extends({
      clientIds: clientIds,
      toggleProps: toggleProps
    }, props));
  }));
}
export default BlockSettingsMenu;
//# sourceMappingURL=index.js.map