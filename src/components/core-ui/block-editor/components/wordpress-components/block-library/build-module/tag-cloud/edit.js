import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { map, filter } from 'lodash';
/**
 * WordPress dependencies
 */

import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

function TagCloudEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      taxonomies = _ref.taxonomies;
  var taxonomy = attributes.taxonomy,
      showTagCounts = attributes.showTagCounts;

  var getTaxonomyOptions = function getTaxonomyOptions() {
    var selectOption = {
      label: __('- Select -'),
      value: '',
      disabled: true
    };
    var taxonomyOptions = map(filter(taxonomies, 'show_cloud'), function (item) {
      return {
        value: item.slug,
        label: item.name
      };
    });
    return [selectOption].concat(_toConsumableArray(taxonomyOptions));
  };

  var inspectorControls = createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Tag Cloud settings')
  }, createElement(SelectControl, {
    label: __('Taxonomy'),
    options: getTaxonomyOptions(),
    value: taxonomy,
    onChange: function onChange(selectedTaxonomy) {
      return setAttributes({
        taxonomy: selectedTaxonomy
      });
    }
  }), createElement(ToggleControl, {
    label: __('Show post counts'),
    checked: showTagCounts,
    onChange: function onChange() {
      return setAttributes({
        showTagCounts: !showTagCounts
      });
    }
  })));
  return createElement(Fragment, null, inspectorControls, createElement(ServerSideRender, {
    key: "tag-cloud",
    block: "core/tag-cloud",
    attributes: attributes
  }));
}

export default withSelect(function (select) {
  return {
    taxonomies: select('core').getTaxonomies()
  };
})(TagCloudEdit);
//# sourceMappingURL=edit.js.map