import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { createContext, useState, useMemo, useContext } from '@wordpress/element';
var QueryContext = createContext();
export default function QueryProvider(_ref) {
  var children = _ref.children;

  var _useState = useState({
    page: 1
  }),
      _useState2 = _slicedToArray(_useState, 2),
      queryContext = _useState2[0],
      setQueryContext = _useState2[1];

  return createElement(QueryContext.Provider, {
    value: useMemo(function () {
      return [queryContext, function (newQueryContext) {
        return setQueryContext(function (currentQueryContext) {
          return _objectSpread(_objectSpread({}, currentQueryContext), newQueryContext);
        });
      }];
    }, [queryContext])
  }, children);
}
export function useQueryContext() {
  return useContext(QueryContext);
}
//# sourceMappingURL=query-provider.js.map