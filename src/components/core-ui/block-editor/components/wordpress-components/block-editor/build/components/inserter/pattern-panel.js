"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
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
    return (0, _classnames.default)('block-editor-inserter__panel-header', 'block-editor-inserter__panel-header-patterns');
  };

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: getPanelHeaderClassName()
  }, (0, _element.createElement)(_components.SelectControl, {
    className: "block-editor-inserter__panel-dropdown",
    label: (0, _i18n.__)('Filter patterns'),
    hideLabelFromVision: true,
    value: selectedCategory.name,
    onChange: onChangeSelect,
    options: categoryOptions()
  })), (0, _element.createElement)("div", {
    className: "block-editor-inserter__panel-content"
  }, children));
}

var _default = PatternInserterPanel;
exports.default = _default;
//# sourceMappingURL=pattern-panel.js.map