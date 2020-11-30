import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var autoplay = attributes.autoplay,
      caption = attributes.caption,
      controls = attributes.controls,
      loop = attributes.loop,
      muted = attributes.muted,
      poster = attributes.poster,
      preload = attributes.preload,
      src = attributes.src,
      playsInline = attributes.playsInline;
  return createElement("figure", null, src && createElement("video", {
    autoPlay: autoplay,
    controls: controls,
    loop: loop,
    muted: muted,
    poster: poster,
    preload: preload !== 'metadata' ? preload : undefined,
    src: src,
    playsInline: playsInline
  }), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map