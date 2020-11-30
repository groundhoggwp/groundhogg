/**
 * WordPress dependencies
 */
import { Children } from '@wordpress/element'; // For native mobile, just shortcircuit the Tooltip to return its child.

var Tooltip = function Tooltip(props) {
  return Children.only(props.children);
};

export default Tooltip;
//# sourceMappingURL=index.native.js.map