"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _blocks = require("@wordpress/blocks");

var _blob = require("@wordpress/blob");

var _shared = require("./shared");

var _constants = require("./constants");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var parseShortcodeIds = function parseShortcodeIds(ids) {
  if (!ids) {
    return [];
  }

  return ids.split(',').map(function (id) {
    return parseInt(id, 10);
  });
};

var transforms = {
  from: [{
    type: 'block',
    isMultiBlock: true,
    blocks: ['core/image'],
    transform: function transform(attributes) {
      // Init the align and size from the first item which may be either the placeholder or an image.
      var _attributes$ = attributes[0],
          align = _attributes$.align,
          sizeSlug = _attributes$.sizeSlug; // Loop through all the images and check if they have the same align and size.

      align = (0, _lodash.every)(attributes, ['align', align]) ? align : undefined;
      sizeSlug = (0, _lodash.every)(attributes, ['sizeSlug', sizeSlug]) ? sizeSlug : undefined;
      var validImages = (0, _lodash.filter)(attributes, function (_ref) {
        var url = _ref.url;
        return url;
      });
      return (0, _blocks.createBlock)('core/gallery', {
        images: validImages.map(function (_ref2) {
          var id = _ref2.id,
              url = _ref2.url,
              alt = _ref2.alt,
              caption = _ref2.caption;
          return {
            id: (0, _lodash.toString)(id),
            url: url,
            alt: alt,
            caption: caption
          };
        }),
        ids: validImages.map(function (_ref3) {
          var id = _ref3.id;
          return parseInt(id, 10);
        }),
        align: align,
        sizeSlug: sizeSlug
      });
    }
  }, {
    type: 'shortcode',
    tag: 'gallery',
    attributes: {
      images: {
        type: 'array',
        shortcode: function shortcode(_ref4) {
          var ids = _ref4.named.ids;
          return parseShortcodeIds(ids).map(function (id) {
            return {
              id: (0, _lodash.toString)(id)
            };
          });
        }
      },
      ids: {
        type: 'array',
        shortcode: function shortcode(_ref5) {
          var ids = _ref5.named.ids;
          return parseShortcodeIds(ids);
        }
      },
      columns: {
        type: 'number',
        shortcode: function shortcode(_ref6) {
          var _ref6$named$columns = _ref6.named.columns,
              columns = _ref6$named$columns === void 0 ? '3' : _ref6$named$columns;
          return parseInt(columns, 10);
        }
      },
      linkTo: {
        type: 'string',
        shortcode: function shortcode(_ref7) {
          var _ref7$named$link = _ref7.named.link,
              link = _ref7$named$link === void 0 ? _constants.LINK_DESTINATION_ATTACHMENT : _ref7$named$link;
          return link;
        }
      }
    },
    isMatch: function isMatch(_ref8) {
      var named = _ref8.named;
      return undefined !== named.ids;
    }
  }, {
    // When created by drag and dropping multiple files on an insertion point
    type: 'files',
    isMatch: function isMatch(files) {
      return files.length !== 1 && (0, _lodash.every)(files, function (file) {
        return file.type.indexOf('image/') === 0;
      });
    },
    transform: function transform(files) {
      var block = (0, _blocks.createBlock)('core/gallery', {
        images: files.map(function (file) {
          return (0, _shared.pickRelevantMediaFiles)({
            url: (0, _blob.createBlobURL)(file)
          });
        })
      });
      return block;
    }
  }],
  to: [{
    type: 'block',
    blocks: ['core/image'],
    transform: function transform(_ref9) {
      var images = _ref9.images,
          align = _ref9.align,
          sizeSlug = _ref9.sizeSlug,
          ids = _ref9.ids;

      if (images.length > 0) {
        return images.map(function (_ref10, index) {
          var url = _ref10.url,
              alt = _ref10.alt,
              caption = _ref10.caption;
          return (0, _blocks.createBlock)('core/image', {
            id: ids[index],
            url: url,
            alt: alt,
            caption: caption,
            align: align,
            sizeSlug: sizeSlug
          });
        });
      }

      return (0, _blocks.createBlock)('core/image', {
        align: align
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map