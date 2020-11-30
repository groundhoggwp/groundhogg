import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
var formatItalic = createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, createElement(Path, {
  d: "M12.5 5L10 19h1.9l2.5-14z"
}));
export default formatItalic;
//# sourceMappingURL=format-italic.js.map