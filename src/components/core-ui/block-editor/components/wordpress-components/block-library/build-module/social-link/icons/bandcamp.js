import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/primitives';
export var BandcampIcon = function BandcampIcon() {
  return createElement(SVG, {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    version: "1.1"
  }, createElement(Path, {
    d: "M15.27 17.289 3 17.289 8.73 6.711 21 6.711 15.27 17.289"
  }));
};
//# sourceMappingURL=bandcamp.js.map