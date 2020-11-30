import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { SVG, Circle } from '@wordpress/primitives';
export var PageControlIcon = function PageControlIcon(_ref) {
  var isSelected = _ref.isSelected;
  return createElement(SVG, {
    width: "8",
    height: "8",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, createElement(Circle, {
    cx: "4",
    cy: "4",
    r: "4",
    fill: isSelected ? '#419ECD' : '#E1E3E6'
  }));
};
//# sourceMappingURL=icons.js.map