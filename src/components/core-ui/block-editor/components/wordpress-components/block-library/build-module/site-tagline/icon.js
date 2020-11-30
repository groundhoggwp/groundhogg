import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/components';
export default createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24"
}, createElement(Path, {
  fill: "none",
  d: "M0 0h24v24H0z"
}), createElement(Path, {
  d: "M4 9h16v2H4V9zm0 4h10v2H4v-2z"
}));
//# sourceMappingURL=icon.js.map