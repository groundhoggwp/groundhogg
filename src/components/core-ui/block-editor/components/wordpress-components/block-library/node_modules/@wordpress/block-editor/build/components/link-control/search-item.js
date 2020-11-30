"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.LinkControlSearchItem = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _url = require("@wordpress/url");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var LinkControlSearchItem = function LinkControlSearchItem(_ref) {
  var itemProps = _ref.itemProps,
      suggestion = _ref.suggestion,
      _ref$isSelected = _ref.isSelected,
      isSelected = _ref$isSelected === void 0 ? false : _ref$isSelected,
      onClick = _ref.onClick,
      _ref$isURL = _ref.isURL,
      isURL = _ref$isURL === void 0 ? false : _ref$isURL,
      _ref$searchTerm = _ref.searchTerm,
      searchTerm = _ref$searchTerm === void 0 ? '' : _ref$searchTerm,
      _ref$shouldShowType = _ref.shouldShowType,
      shouldShowType = _ref$shouldShowType === void 0 ? false : _ref$shouldShowType;
  return (0, _element.createElement)(_components.Button, (0, _extends2.default)({}, itemProps, {
    onClick: onClick,
    className: (0, _classnames.default)('block-editor-link-control__search-item', {
      'is-selected': isSelected,
      'is-url': isURL,
      'is-entity': !isURL
    })
  }), isURL && (0, _element.createElement)(_icons.Icon, {
    className: "block-editor-link-control__search-item-icon",
    icon: _icons.globe
  }), (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-header"
  }, (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-title"
  }, (0, _element.createElement)(_components.TextHighlight, {
    text: suggestion.title,
    highlight: searchTerm
  })), (0, _element.createElement)("span", {
    "aria-hidden": !isURL,
    className: "block-editor-link-control__search-item-info"
  }, !isURL && ((0, _url.filterURLForDisplay)((0, _url.safeDecodeURI)(suggestion.url)) || ''), isURL && (0, _i18n.__)('Press ENTER to add this link'))), shouldShowType && suggestion.type && (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-item-type"
  }, suggestion.type === 'post_tag' ? 'tag' : suggestion.type));
};

exports.LinkControlSearchItem = LinkControlSearchItem;
var _default = LinkControlSearchItem;
exports.default = _default;
//# sourceMappingURL=search-item.js.map