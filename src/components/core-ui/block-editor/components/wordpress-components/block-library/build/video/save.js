"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function save(_ref) {
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
  return (0, _element.createElement)("figure", null, src && (0, _element.createElement)("video", {
    autoPlay: autoplay,
    controls: controls,
    loop: loop,
    muted: muted,
    poster: poster,
    preload: preload !== 'metadata' ? preload : undefined,
    src: src,
    playsInline: playsInline
  }), !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map