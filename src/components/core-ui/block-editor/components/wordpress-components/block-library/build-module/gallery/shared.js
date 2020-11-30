/**
 * External dependencies
 */
import { get, pick } from 'lodash';
export function defaultColumnsNumber(attributes) {
  return Math.min(3, attributes.images.length);
}
export var pickRelevantMediaFiles = function pickRelevantMediaFiles(image) {
  var sizeSlug = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'large';
  var imageProps = pick(image, ['alt', 'id', 'link', 'caption']);
  imageProps.url = get(image, ['sizes', sizeSlug, 'url']) || get(image, ['media_details', 'sizes', sizeSlug, 'source_url']) || image.url;
  var fullUrl = get(image, ['sizes', 'full', 'url']) || get(image, ['media_details', 'sizes', 'full', 'source_url']);

  if (fullUrl) {
    imageProps.fullUrl = fullUrl;
  }

  return imageProps;
};
//# sourceMappingURL=shared.js.map