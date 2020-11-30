import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useEntityProp, useEntityId } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { ResponsiveWrapper } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function PostFeaturedImageDisplay() {
  var _useEntityProp = useEntityProp('postType', 'post', 'featured_media'),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      featuredImage = _useEntityProp2[0];

  var media = useSelect(function (select) {
    return featuredImage && select('core').getMedia(featuredImage);
  }, [featuredImage]);
  return media ? createElement(ResponsiveWrapper, {
    naturalWidth: media.media_details.width,
    naturalHeight: media.media_details.height
  }, createElement("img", {
    src: media.source_url,
    alt: "Post Featured Media"
  })) : null;
}

export default function PostFeaturedImageEdit() {
  if (!useEntityId('postType', 'post')) {
    return __('Post Featured Image');
  }

  return createElement(PostFeaturedImageDisplay, null);
}
//# sourceMappingURL=edit.js.map