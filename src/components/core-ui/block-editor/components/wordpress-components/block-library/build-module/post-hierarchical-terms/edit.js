import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { find } from 'lodash';
/**
 * WordPress dependencies
 */

import { AlignmentToolbar, BlockControls, Warning, __experimentalUseBlockWrapperProps as useBlockWrapperProps, __experimentalBlockVariationPicker as BlockVariationPicker } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import useHierarchicalTermLinks from './use-hierarchical-term-links';
export default function PostHierarchicalTermsEdit(_ref) {
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

  var _useSelect = useSelect(function (select) {
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

  var selectedTerm = useSelect(function (select) {
    if (!term) return {};
    var taxonomies = select('core').getTaxonomies({
      per_page: -1
    });
    return find(taxonomies, function (taxonomy) {
      return taxonomy.slug === term && taxonomy.hierarchical && taxonomy.visibility.show_ui;
    }) || {};
  }, [term]);

  var _useHierarchicalTermL = useHierarchicalTermLinks({
    postId: postId,
    postType: postType,
    term: selectedTerm
  }),
      hierarchicalTermLinks = _useHierarchicalTermL.hierarchicalTermLinks,
      isLoadingHierarchicalTermLinks = _useHierarchicalTermL.isLoadingHierarchicalTermLinks;

  var hasPost = postId && postType;
  var hasHierarchicalTermLinks = hierarchicalTermLinks && hierarchicalTermLinks.length > 0;
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!hasPost) {
    return createElement("div", blockWrapperProps, createElement(Warning, null, __('Post Hierarchical Terms block: post not found.')));
  }

  if (!term) {
    var _blockType$icon;

    return createElement("div", blockWrapperProps, createElement(BlockVariationPicker, {
      icon: blockType === null || blockType === void 0 ? void 0 : (_blockType$icon = blockType.icon) === null || _blockType$icon === void 0 ? void 0 : _blockType$icon.src,
      label: blockType === null || blockType === void 0 ? void 0 : blockType.title,
      onSelect: function onSelect() {
        var variation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultVariation;
        setAttributes(variation.attributes);
      },
      variations: variations
    }));
  }

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement("div", blockWrapperProps, isLoadingHierarchicalTermLinks && createElement(Spinner, null), hasHierarchicalTermLinks && !isLoadingHierarchicalTermLinks && hierarchicalTermLinks.reduce(function (prev, curr) {
    return [prev, ' | ', curr];
  }), !isLoadingHierarchicalTermLinks && !hasHierarchicalTermLinks && ( // eslint-disable-next-line camelcase
  (selectedTerm === null || selectedTerm === void 0 ? void 0 : (_selectedTerm$labels = selectedTerm.labels) === null || _selectedTerm$labels === void 0 ? void 0 : _selectedTerm$labels.no_terms) || __('Term items not found.'))));
}
//# sourceMappingURL=edit.js.map