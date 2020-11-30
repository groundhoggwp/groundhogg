"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = QueryProvider;
exports.useQueryContext = useQueryContext;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var QueryContext = (0, _element.createContext)();

function QueryProvider(_ref) {
  var children = _ref.children;

  var _useState = (0, _element.useState)({
    page: 1
  }),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      queryContext = _useState2[0],
      setQueryContext = _useState2[1];

  return (0, _element.createElement)(QueryContext.Provider, {
    value: (0, _element.useMemo)(function () {
      return [queryContext, function (newQueryContext) {
        return setQueryContext(function (currentQueryContext) {
          return _objectSpread(_objectSpread({}, currentQueryContext), newQueryContext);
        });
      }];
    }, [queryContext])
  }, children);
}

function useQueryContext() {
  return (0, _element.useContext)(QueryContext);
}
//# sourceMappingURL=query-provider.js.map