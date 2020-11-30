"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _blob = require("@wordpress/blob");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var transforms = {
  from: [{
    type: 'files',
    isMatch: function isMatch(files) {
      return files.length > 0;
    },
    // We define a lower priorty (higher number) than the default of 10. This
    // ensures that the File block is only created as a fallback.
    priority: 15,
    transform: function transform(files) {
      var blocks = [];
      files.forEach(function (file) {
        var blobURL = (0, _blob.createBlobURL)(file); // File will be uploaded in componentDidMount()

        blocks.push((0, _blocks.createBlock)('core/file', {
          href: blobURL,
          fileName: file.name,
          textLinkHref: blobURL
        }));
      });
      return blocks;
    }
  }, {
    type: 'block',
    blocks: ['core/audio'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/file', {
        href: attributes.src,
        fileName: attributes.caption,
        textLinkHref: attributes.src,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/video'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/file', {
        href: attributes.src,
        fileName: attributes.caption,
        textLinkHref: attributes.src,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/image'],
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/file', {
        href: attributes.url,
        fileName: attributes.caption,
        textLinkHref: attributes.url,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/audio'],
    isMatch: function isMatch(_ref) {
      var id = _ref.id;

      if (!id) {
        return false;
      }

      var _select = (0, _data.select)('core'),
          getMedia = _select.getMedia;

      var media = getMedia(id);
      return !!media && (0, _lodash.includes)(media.mime_type, 'audio');
    },
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/audio', {
        src: attributes.href,
        caption: attributes.fileName,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/video'],
    isMatch: function isMatch(_ref2) {
      var id = _ref2.id;

      if (!id) {
        return false;
      }

      var _select2 = (0, _data.select)('core'),
          getMedia = _select2.getMedia;

      var media = getMedia(id);
      return !!media && (0, _lodash.includes)(media.mime_type, 'video');
    },
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/video', {
        src: attributes.href,
        caption: attributes.fileName,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }, {
    type: 'block',
    blocks: ['core/image'],
    isMatch: function isMatch(_ref3) {
      var id = _ref3.id;

      if (!id) {
        return false;
      }

      var _select3 = (0, _data.select)('core'),
          getMedia = _select3.getMedia;

      var media = getMedia(id);
      return !!media && (0, _lodash.includes)(media.mime_type, 'image');
    },
    transform: function transform(attributes) {
      return (0, _blocks.createBlock)('core/image', {
        url: attributes.href,
        caption: attributes.fileName,
        id: attributes.id,
        anchor: attributes.anchor
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map