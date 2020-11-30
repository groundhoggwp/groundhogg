/**
 * External dependencies
 */
import { filter, every, toString } from 'lodash';
/**
 * WordPress dependencies
 */

import { createBlock } from '@wordpress/blocks';
import { createBlobURL } from '@wordpress/blob';
/**
 * Internal dependencies
 */

import { pickRelevantMediaFiles } from './shared';
import { LINK_DESTINATION_ATTACHMENT } from './constants';

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

      align = every(attributes, ['align', align]) ? align : undefined;
      sizeSlug = every(attributes, ['sizeSlug', sizeSlug]) ? sizeSlug : undefined;
      var validImages = filter(attributes, function (_ref) {
        var url = _ref.url;
        return url;
      });
      return createBlock('core/gallery', {
        images: validImages.map(function (_ref2) {
          var id = _ref2.id,
              url = _ref2.url,
              alt = _ref2.alt,
              caption = _ref2.caption;
          return {
            id: toString(id),
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
              id: toString(id)
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
              link = _ref7$named$link === void 0 ? LINK_DESTINATION_ATTACHMENT : _ref7$named$link;
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
      return files.length !== 1 && every(files, function (file) {
        return file.type.indexOf('image/') === 0;
      });
    },
    transform: function transform(files) {
      var block = createBlock('core/gallery', {
        images: files.map(function (file) {
          return pickRelevantMediaFiles({
            url: createBlobURL(file)
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
          return createBlock('core/image', {
            id: ids[index],
            url: url,
            alt: alt,
            caption: caption,
            align: align,
            sizeSlug: sizeSlug
          });
        });
      }

      return createBlock('core/image', {
        align: align
      });
    }
  }]
};
export default transforms;
//# sourceMappingURL=transforms.js.map