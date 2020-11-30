import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var autoplay = attributes.autoplay,
      caption = attributes.caption,
      loop = attributes.loop,
      preload = attributes.preload,
      src = attributes.src;
  return src && createElement("figure", null, createElement("audio", {
    controls: "controls",
    src: src,
    autoPlay: autoplay,
    loop: loop,
    preload: preload
  }), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map