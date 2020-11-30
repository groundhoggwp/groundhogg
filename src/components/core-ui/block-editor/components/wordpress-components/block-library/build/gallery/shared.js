"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.defaultColumnsNumber = defaultColumnsNumber;
exports.pickRelevantMediaFiles = void 0;

var _lodash = require("lodash");

/**
 * External dependencies
 */
function defaultColumnsNumber(attributes) {
  return Math.min(3, attributes.images.length);
}

var pickRelevantMediaFiles = function pickRelevantMediaFiles(image) {
  var sizeSlug = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'large';
  var imageProps = (0, _lodash.pick)(image, ['alt', 'id', 'link', 'caption']);
  imageProps.url = (0, _lodash.get)(image, ['sizes', sizeSlug, 'url']) || (0, _lodash.get)(image, ['media_details', 'sizes', sizeSlug, 'source_url']) || image.url;
  var fullUrl = (0, _lodash.get)(image, ['sizes', 'full', 'url']) || (0, _lodash.get)(image, ['media_details', 'sizes', 'full', 'source_url']);

  if (fullUrl) {
    imageProps.fullUrl = fullUrl;
  }

  return imageProps;
};

exports.pickRelevantMediaFiles = pickRelevantMediaFiles;
//# sourceMappingURL=shared.js.map