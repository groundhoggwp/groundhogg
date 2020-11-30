import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function PatternInserterPanel(_ref) {
  var selectedCategory = _ref.selectedCategory,
      patternCategories = _ref.patternCategories,
      onClickCategory = _ref.onClickCategory,
      children = _ref.children;

  var categoryOptions = function categoryOptions() {
    var options = [];
    patternCategories.map(function (patternCategory) {
      return options.push({
        value: patternCategory.name,
        label: patternCategory.label
      });
    });
    return options;
  };

  var onChangeSelect = function onChangeSelect(selected) {
    onClickCategory(patternCategories.find(function (patternCategory) {
      return selected === patternCategory.name;
    }));
  };

  var getPanelHeaderClassName = function getPanelHeaderClassName() {
    return classnames('block-editor-inserter__panel-header', 'block-editor-inserter__panel-header-patterns');
  };

  return createElement(Fragment, null, createElement("div", {
    className: getPanelHeaderClassName()
  }, createElement(SelectControl, {
    className: "block-editor-inserter__panel-dropdown",
    label: __('Filter patterns'),
    hideLabelFromVision: true,
    value: selectedCategory.name,
    onChange: onChangeSelect,
    options: categoryOptions()
  })), createElement("div", {
    className: "block-editor-inserter__panel-content"
  }, children));
}

export default PatternInserterPanel;
//# sourceMappingURL=pattern-panel.js.map