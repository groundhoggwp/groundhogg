import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
export default function useHierarchicalTermLinks(_ref) {
  var postId = _ref.postId,
      postType = _ref.postType,
      term = _ref.term;
  var restBase = term.rest_base,
      slug = term.slug;

  var _useEntityProp = useEntityProp('postType', postType, restBase, postId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      hierarchicalTermItems = _useEntityProp2[0];

  var _useSelect = useSelect(function (select) {
    var _select = select('core'),
        getEntityRecord = _select.getEntityRecord;

    var loaded = true;
    var links = map(hierarchicalTermItems, function (itemId) {
      var item = getEntityRecord('taxonomy', slug, itemId);

      if (!item) {
        return loaded = false;
      }

      return createElement("a", {
        key: itemId,
        href: item.link
      }, item.name);
    });
    return {
      hierarchicalTermLinks: links,
      isLoadingHierarchicalTermLinks: !loaded
    };
  }, [hierarchicalTermItems]),
      hierarchicalTermLinks = _useSelect.hierarchicalTermLinks,
      isLoadingHierarchicalTermLinks = _useSelect.isLoadingHierarchicalTermLinks;

  return {
    hierarchicalTermLinks: hierarchicalTermLinks,
    isLoadingHierarchicalTermLinks: isLoadingHierarchicalTermLinks
  };
}
//# sourceMappingURL=use-hierarchical-term-links.js.map