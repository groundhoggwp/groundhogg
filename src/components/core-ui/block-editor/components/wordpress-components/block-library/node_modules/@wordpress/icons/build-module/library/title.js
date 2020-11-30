import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
var title = createElement(SVG, {
  xmlns: "https://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, createElement(Path, {
  d: "M5 4v3h5.5v12h3V7H19V4H5z"
}));
export default title;
//# sourceMappingURL=title.js.map