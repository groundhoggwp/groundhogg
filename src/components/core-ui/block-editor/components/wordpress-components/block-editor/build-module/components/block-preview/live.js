import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Disabled } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockList from '../block-list';
export default function LiveBlockPreview(_ref) {
  var onClick = _ref.onClick;
  return createElement("div", {
    tabIndex: 0,
    role: "button",
    onClick: onClick,
    onKeyPress: onClick
  }, createElement(Disabled, null, createElement(BlockList, null)));
}
//# sourceMappingURL=live.js.map