"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _serverSideRender = _interopRequireDefault(require("@wordpress/server-side-render"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function TagCloudEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      taxonomies = _ref.taxonomies;
  var taxonomy = attributes.taxonomy,
      showTagCounts = attributes.showTagCounts;

  var getTaxonomyOptions = function getTaxonomyOptions() {
    var selectOption = {
      label: (0, _i18n.__)('- Select -'),
      value: '',
      disabled: true
    };
    var taxonomyOptions = (0, _lodash.map)((0, _lodash.filter)(taxonomies, 'show_cloud'), function (item) {
      return {
        value: item.slug,
        label: item.name
      };
    });
    return [selectOption].concat((0, _toConsumableArray2.default)(taxonomyOptions));
  };

  var inspectorControls = (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Tag Cloud settings')
  }, (0, _element.createElement)(_components.SelectControl, {
    label: (0, _i18n.__)('Taxonomy'),
    options: getTaxonomyOptions(),
    value: taxonomy,
    onChange: function onChange(selectedTaxonomy) {
      return setAttributes({
        taxonomy: selectedTaxonomy
      });
    }
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Show post counts'),
    checked: showTagCounts,
    onChange: function onChange() {
      return setAttributes({
        showTagCounts: !showTagCounts
      });
    }
  })));
  return (0, _element.createElement)(_element.Fragment, null, inspectorControls, (0, _element.createElement)(_serverSideRender.default, {
    key: "tag-cloud",
    block: "core/tag-cloud",
    attributes: attributes
  }));
}

var _default = (0, _data.withSelect)(function (select) {
  return {
    taxonomies: select('core').getTaxonomies()
  };
})(TagCloudEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map