import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
var stretchFullWidth = createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, createElement(Path, {
  d: "M5 4v11h14V4H5zm3 15.8h8v-1.5H8v1.5z"
}));
export default stretchFullWidth;
//# sourceMappingURL=stretch-full-width.js.map