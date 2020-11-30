import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { BlockControls, InnerBlocks, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import QueryToolbar from './query-toolbar';
import QueryProvider from './query-provider';
import QueryInspectorControls from './query-inspector-controls';
var TEMPLATE = [['core/query-loop'], ['core/query-pagination']];
export default function QueryEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      queryId = _ref$attributes.queryId,
      query = _ref$attributes.query,
      setAttributes = _ref.setAttributes;
  var instanceId = useInstanceId(QueryEdit);
  var blockWrapperProps = useBlockWrapperProps(); // We need this for multi-query block pagination.
  // Query parameters for each block are scoped to their ID.

  useEffect(function () {
    if (!queryId) {
      setAttributes({
        queryId: instanceId
      });
    }
  }, [queryId, instanceId]);

  var updateQuery = function updateQuery(newQuery) {
    return setAttributes({
      query: _objectSpread(_objectSpread({}, query), newQuery)
    });
  };

  return createElement(Fragment, null, createElement(QueryInspectorControls, {
    query: query,
    setQuery: updateQuery
  }), createElement(BlockControls, null, createElement(QueryToolbar, {
    query: query,
    setQuery: updateQuery
  })), createElement("div", blockWrapperProps, createElement(QueryProvider, null, createElement(InnerBlocks, {
    template: TEMPLATE
  }))));
}
export * from './query-provider';
//# sourceMappingURL=index.js.map