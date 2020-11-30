"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _blocks = require("@wordpress/blocks");

var _shared = require("./shared");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/image'],
    transform: function transform(_ref) {
      var caption = _ref.caption,
          url = _ref.url,
          align = _ref.align,
          id = _ref.id,
          anchor = _ref.anchor;
      return (0, _blocks.createBlock)('core/cover', {
        title: caption,
        url: url,
        align: align,
        id: id,
        anchor: anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/video'],
    transform: function transform(_ref2) {
      var caption = _ref2.caption,
          src = _ref2.src,
          align = _ref2.align,
          id = _ref2.id,
          anchor = _ref2.anchor;
      return (0, _blocks.createBlock)('core/cover', {
        title: caption,
        url: src,
        align: align,
        id: id,
        backgroundType: _shared.VIDEO_BACKGROUND_TYPE,
        anchor: anchor
      });
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/image'],
    isMatch: function isMatch(_ref3) {
      var backgroundType = _ref3.backgroundType,
          url = _ref3.url,
          overlayColor = _ref3.overlayColor,
          customOverlayColor = _ref3.customOverlayColor,
          gradient = _ref3.gradient,
          customGradient = _ref3.customGradient;

      if (url) {
        // If a url exists the transform could happen if that URL represents an image background.
        return backgroundType === _shared.IMAGE_BACKGROUND_TYPE;
      } // If a url is not set the transform could happen if the cover has no background color or gradient;


      return !overlayColor && !customOverlayColor && !gradient && !customGradient;
    },
    transform: function transform(_ref4) {
      var title = _ref4.title,
          url = _ref4.url,
          align = _ref4.align,
          id = _ref4.id,
          anchor = _ref4.anchor;
      return (0, _blocks.createBlock)('core/image', {
        caption: title,
        url: url,
        align: align,
        id: id,
        anchor: anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/video'],
    isMatch: function isMatch(_ref5) {
      var backgroundType = _ref5.backgroundType,
          url = _ref5.url,
          overlayColor = _ref5.overlayColor,
          customOverlayColor = _ref5.customOverlayColor,
          gradient = _ref5.gradient,
          customGradient = _ref5.customGradient;

      if (url) {
        // If a url exists the transform could happen if that URL represents a video background.
        return backgroundType === _shared.VIDEO_BACKGROUND_TYPE;
      } // If a url is not set the transform could happen if the cover has no background color or gradient;


      return !overlayColor && !customOverlayColor && !gradient && !customGradient;
    },
    transform: function transform(_ref6) {
      var title = _ref6.title,
          url = _ref6.url,
          align = _ref6.align,
          id = _ref6.id,
          anchor = _ref6.anchor;
      return (0, _blocks.createBlock)('core/video', {
        caption: title,
        src: url,
        id: id,
        align: align,
        anchor: anchor
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map