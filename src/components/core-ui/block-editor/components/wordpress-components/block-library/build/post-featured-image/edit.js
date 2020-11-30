"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostFeaturedImageEdit;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _coreData = require("@wordpress/core-data");

var _data = require("@wordpress/data");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
function PostFeaturedImageDisplay() {
  var _useEntityProp = (0, _coreData.useEntityProp)('postType', 'post', 'featured_media'),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      featuredImage = _useEntityProp2[0];

  var media = (0, _data.useSelect)(function (select) {
    return featuredImage && select('core').getMedia(featuredImage);
  }, [featuredImage]);
  return media ? (0, _element.createElement)(_components.ResponsiveWrapper, {
    naturalWidth: media.media_details.width,
    naturalHeight: media.media_details.height
  }, (0, _element.createElement)("img", {
    src: media.source_url,
    alt: "Post Featured Media"
  })) : null;
}

function PostFeaturedImageEdit() {
  if (!(0, _coreData.useEntityId)('postType', 'post')) {
    return (0, _i18n.__)('Post Featured Image');
  }

  return (0, _element.createElement)(PostFeaturedImageDisplay, null);
}
//# sourceMappingURL=edit.js.map