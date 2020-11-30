import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { PanelBody, QueryControls } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
export default function QueryInspectorControls(_ref) {
  var query = _ref.query,
      setQuery = _ref.setQuery;
  var order = query.order,
      orderBy = query.orderBy,
      selectedAuthorId = query.author;

  var _useSelect = useSelect(function (select) {
    var _select = select('core'),
        getEntityRecords = _select.getEntityRecords;

    return {
      authorList: getEntityRecords('root', 'user', {
        per_page: -1
      })
    };
  }, []),
      authorList = _useSelect.authorList;

  return createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Sorting and filtering')
  }, createElement(QueryControls, _extends({
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