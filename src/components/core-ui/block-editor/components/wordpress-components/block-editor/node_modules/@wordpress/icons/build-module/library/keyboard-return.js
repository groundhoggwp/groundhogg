import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
var keyboardReturn = createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "-2 -2 24 24"
}, createElement(Path, {
  d: "M16 4h2v9H7v3l-5-4 5-4v3h9V4z"
}));
export default keyboardReturn;
//# sourceMappingURL=keyboard-return.js.map