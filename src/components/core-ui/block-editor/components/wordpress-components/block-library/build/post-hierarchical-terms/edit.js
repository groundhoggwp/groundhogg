"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostHierarchicalTermsEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _useHierarchicalTermLinks = _interopRequireDefault(require("./use-hierarchical-term-links"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PostHierarchicalTermsEdit(_ref) {
  var _selectedTerm$labels;

  var attributes = _ref.attributes,
      clientId = _ref.clientId,
      context = _ref.context,
      name = _ref.name,
      setAttributes = _ref.setAttributes;
  var term = attributes.term,
      textAlign = attributes.textAlign;
  var postId = context.postId,
      postType = context.postType;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/blocks'),
        getBlockVariations = _select.getBlockVariations,
        getBlockType = _select.getBlockType,
        getDefaultBlockVariation = _select.getDefaultBlockVariation;

    return {
      blockType: getBlockType(name),
      defaultVariation: getDefaultBlockVariation(name, 'block'),
      variations: getBlockVariations(name, 'block')
    };
  }, [clientId, name]),
      blockType = _useSelect.blockType,
      defaultVariation = _useSelect.defaultVariation,
      variations = _useSelect.variations;

  var selectedTerm = (0, _data.useSelect)(function (select) {
    if (!term) return {};
    var taxonomies = select('core').getTaxonomies({
      per_page: -1
    });
    return (0, _lodash.find)(taxonomies, function (taxonomy) {
      return taxonomy.slug === term && taxonomy.hierarchical && taxonomy.visibility.show_ui;
    }) || {};
  }, [term]);

  var _useHierarchicalTermL = (0, _useHierarchicalTermLinks.default)({
    postId: postId,
    postType: postType,
    term: selectedTerm
  }),
      hierarchicalTermLinks = _useHierarchicalTermL.hierarchicalTermLinks,
      isLoadingHierarchicalTermLinks = _useHierarchicalTermL.isLoadingHierarchicalTermLinks;

  var hasPost = postId && postType;
  var hasHierarchicalTermLinks = hierarchicalTermLinks && hierarchicalTermLinks.length > 0;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!hasPost) {
    return (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post Hierarchical Terms block: post not found.')));
  }

  if (!term) {
    var _blockType$icon;

    return (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.__experimentalBlockVariationPicker, {
      icon: blockType === null || blockType === void 0 ? void 0 : (_blockType$icon = blockType.icon) === null || _blockType$icon === void 0 ? void 0 : _blockType$icon.src,
      label: blockType === null || blockType === void 0 ? void 0 : blockType.title,
      onSelect: function onSelect() {
        var variation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultVariation;
        setAttributes(variation.attributes);
      },
      variations: variations
    }));
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)("div", blockWrapperProps, isLoadingHierarchicalTermLinks && (0, _element.createElement)(_components.Spinner, null), hasHierarchicalTermLinks && !isLoadingHierarchicalTermLinks && hierarchicalTermLinks.reduce(function (prev, curr) {
    return [prev, ' | ', curr];
  }), !isLoadingHierarchicalTermLinks && !hasHierarchicalTermLinks && ( // eslint-disable-next-line camelcase
  (selectedTerm === null || selectedTerm === void 0 ? void 0 : (_selectedTerm$labels = selectedTerm.labels) === null || _selectedTerm$labels === void 0 ? void 0 : _selectedTerm$labels.no_terms) || (0, _i18n.__)('Term items not found.'))));
}
//# sourceMappingURL=edit.js.map