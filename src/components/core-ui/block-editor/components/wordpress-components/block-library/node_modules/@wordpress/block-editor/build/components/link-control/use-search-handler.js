"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useSearchHandler;
exports.handleDirectEntry = exports.handleNoop = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _asyncToGenerator2 = _interopRequireDefault(require("@babel/runtime/helpers/asyncToGenerator"));

var _url = require("@wordpress/url");

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _lodash = require("lodash");

var _isUrlLike = _interopRequireDefault(require("./is-url-like"));

var _constants = require("./constants");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var handleNoop = function handleNoop() {
  return Promise.resolve([]);
};

exports.handleNoop = handleNoop;

var handleDirectEntry = function handleDirectEntry(val) {
  var type = 'URL';
  var protocol = (0, _url.getProtocol)(val) || '';

  if (protocol.includes('mailto')) {
    type = 'mailto';
  }

  if (protocol.includes('tel')) {
    type = 'tel';
  }

  if ((0, _lodash.startsWith)(val, '#')) {
    type = 'internal';
  }

  return Promise.resolve([{
    id: val,
    title: val,
    url: type === 'URL' ? (0, _url.prependHTTP)(val) : val,
    type: type
  }]);
};

exports.handleDirectEntry = handleDirectEntry;

var handleEntitySearch = /*#__PURE__*/function () {
  var _ref = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee(val, suggestionsQuery, fetchSearchSuggestions, directEntryHandler, withCreateSuggestion, withURLSuggestion) {
    var isInitialSuggestions, results, couldBeURL;
    return _regenerator.default.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            isInitialSuggestions = suggestionsQuery.isInitialSuggestions;
            _context.next = 3;
            return Promise.all([fetchSearchSuggestions(val, suggestionsQuery), directEntryHandler(val)]);

          case 3:
            results = _context.sent;
            couldBeURL = !val.includes(' '); // If it's potentially a URL search then concat on a URL search suggestion
            // just for good measure. That way once the actual results run out we always
            // have a URL option to fallback on.

            if (couldBeURL && withURLSuggestion && !isInitialSuggestions) {
              results = results[0].concat(results[1]);
            } else {
              results = results[0];
            } // If displaying initial suggestions just return plain results.


            if (!isInitialSuggestions) {
              _context.next = 8;
              break;
            }

            return _context.abrupt("return", results);

          case 8:
            return _context.abrupt("return", (0, _isUrlLike.default)(val) || !withCreateSuggestion ? results : results.concat({
              // the `id` prop is intentionally ommitted here because it
              // is never exposed as part of the component's public API.
              // see: https://github.com/WordPress/gutenberg/pull/19775#discussion_r378931316.
              title: val,
              // must match the existing `<input>`s text value
              url: val,
              // must match the existing `<input>`s text value
              type: _constants.CREATE_TYPE
            }));

          case 9:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));

  return function handleEntitySearch(_x, _x2, _x3, _x4, _x5, _x6) {
    return _ref.apply(this, arguments);
  };
}();

function useSearchHandler(suggestionsQuery, allowDirectEntry, withCreateSuggestion, withURLSuggestion) {
  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return {
      fetchSearchSuggestions: getSettings().__experimentalFetchLinkSuggestions
    };
  }, []),
      fetchSearchSuggestions = _useSelect.fetchSearchSuggestions;

  var directEntryHandler = allowDirectEntry ? handleDirectEntry : handleNoop;
  return (0, _element.useCallback)(function (val, _ref2) {
    var isInitialSuggestions = _ref2.isInitialSuggestions;
    return (0, _isUrlLike.default)(val) ? directEntryHandler(val, {
      isInitialSuggestions: isInitialSuggestions
    }) : handleEntitySearch(val, _objectSpread(_objectSpread({}, suggestionsQuery), {}, {
      isInitialSuggestions: isInitialSuggestions
    }), fetchSearchSuggestions, directEntryHandler, withCreateSuggestion, withURLSuggestion);
  }, [directEntryHandler, fetchSearchSuggestions, withCreateSuggestion]);
}
//# sourceMappingURL=use-search-handler.js.map