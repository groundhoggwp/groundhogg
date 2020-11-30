"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useHierarchicalTermLinks;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _coreData = require("@wordpress/core-data");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function useHierarchicalTermLinks(_ref) {
  var postId = _ref.postId,
      postType = _ref.postType,
      term = _ref.term;
  var restBase = term.rest_base,
      slug = term.slug;

  var _useEntityProp = (0, _coreData.useEntityProp)('postType', postType, restBase, postId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      hierarchicalTermItems = _useEntityProp2[0];

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getEntityRecord = _select.getEntityRecord;

    var loaded = true;
    var links = (0, _lodash.map)(hierarchicalTermItems, function (itemId) {
      var item = getEntityRecord('taxonomy', slug, itemId);

      if (!item) {
        return loaded = false;
      }

      return (0, _element.createElement)("a", {
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