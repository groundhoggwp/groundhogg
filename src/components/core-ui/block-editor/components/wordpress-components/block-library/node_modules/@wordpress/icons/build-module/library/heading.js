import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
var heading = createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, createElement(Path, {
  d: "M6.2 5.2v13.4l5.8-4.8 5.8 4.8V5.2z"
}));
export default heading;
//# sourceMappingURL=heading.js.map