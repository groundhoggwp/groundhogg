"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = QueryInspectorControls;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

/**
 * WordPress dependencies
 */
function QueryInspectorControls(_ref) {
  var query = _ref.query,
      setQuery = _ref.setQuery;
  var order = query.order,
      orderBy = query.orderBy,
      selectedAuthorId = query.author;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getEntityRecords = _select.getEntityRecords;

    return {
      authorList: getEntityRecords('root', 'user', {
        per_page: -1
      })
    };
  }, []),
      authorList = _useSelect.authorList;

  return (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Sorting and filtering')
  }, (0, _element.createElement)(_components.QueryControls, (0, _extends2.default)({
    order: order,
    orderBy: orderBy,
    selectedAuthorId: selectedAuthorId,
    authorList: authorList
  }, {
    onOrderChange: function onOrderChange(value) {
      return setQuery({
        order: value
      });
    },
    onOrderByChange: function onOrderByChange(value) {
      return setQuery({
        orderBy: value
      });
    },
    onAuthorChange: function onAuthorChange(value) {
      return setQuery({
        author: value !== '' ? +value : undefined
      });
    }
  }))));
}
//# sourceMappingURL=query-inspector-controls.js.map