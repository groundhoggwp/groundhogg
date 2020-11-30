"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _asyncToGenerator2 = _interopRequireDefault(require("@babel/runtime/helpers/asyncToGenerator"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _ = require("../");

var _searchResults = _interopRequireDefault(require("./search-results"));

var _constants = require("./constants");

var _useSearchHandler = _interopRequireDefault(require("./use-search-handler"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var noopSearchHandler = Promise.resolve([]);
var LinkControlSearchInput = (0, _element.forwardRef)(function (_ref, ref) {
  var value = _ref.value,
      children = _ref.children,
      _ref$currentLink = _ref.currentLink,
      currentLink = _ref$currentLink === void 0 ? {} : _ref$currentLink,
      _ref$className = _ref.className,
      className = _ref$className === void 0 ? null : _ref$className,
      _ref$placeholder = _ref.placeholder,
      placeholder = _ref$placeholder === void 0 ? null : _ref$placeholder,
      _ref$withCreateSugges = _ref.withCreateSuggestion,
      withCreateSuggestion = _ref$withCreateSugges === void 0 ? false : _ref$withCreateSugges,
      _ref$onCreateSuggesti = _ref.onCreateSuggestion,
      onCreateSuggestion = _ref$onCreateSuggesti === void 0 ? _lodash.noop : _ref$onCreateSuggesti,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$onSelect = _ref.onSelect,
      onSelect = _ref$onSelect === void 0 ? _lodash.noop : _ref$onSelect,
      _ref$showSuggestions = _ref.showSuggestions,
      showSuggestions = _ref$showSuggestions === void 0 ? true : _ref$showSuggestions,
      _ref$renderSuggestion = _ref.renderSuggestions,
      renderSuggestions = _ref$renderSuggestion === void 0 ? function (props) {
    return (0, _element.createElement)(_searchResults.default, props);
  } : _ref$renderSuggestion,
      _ref$fetchSuggestions = _ref.fetchSuggestions,
      fetchSuggestions = _ref$fetchSuggestions === void 0 ? null : _ref$fetchSuggestions,
      _ref$allowDirectEntry = _ref.allowDirectEntry,
      allowDirectEntry = _ref$allowDirectEntry === void 0 ? true : _ref$allowDirectEntry,
      _ref$showInitialSugge = _ref.showInitialSuggestions,
      showInitialSuggestions = _ref$showInitialSugge === void 0 ? false : _ref$showInitialSugge,
      _ref$suggestionsQuery = _ref.suggestionsQuery,
      suggestionsQuery = _ref$suggestionsQuery === void 0 ? {} : _ref$suggestionsQuery,
      _ref$withURLSuggestio = _ref.withURLSuggestion,
      withURLSuggestion = _ref$withURLSuggestio === void 0 ? true : _ref$withURLSuggestio,
      createSuggestionButtonText = _ref.createSuggestionButtonText;
  var genericSearchHandler = (0, _useSearchHandler.default)(suggestionsQuery, allowDirectEntry, withCreateSuggestion, withURLSuggestion);
  var searchHandler = showSuggestions ? fetchSuggestions || genericSearchHandler : noopSearchHandler;
  var instanceId = (0, _compose.useInstanceId)(LinkControlSearchInput);

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      focusedSuggestion = _useState2[0],
      setFocusedSuggestion = _useState2[1];
  /**
   * Handles the user moving between different suggestions. Does not handle
   * choosing an individual item.
   *
   * @param {string} selection the url of the selected suggestion.
   * @param {Object} suggestion the suggestion object.
   */


  var onInputChange = function onInputChange(selection, suggestion) {
    onChange(selection);
    setFocusedSuggestion(suggestion);
  };

  var onFormSubmit = function onFormSubmit(event) {
    event.preventDefault();
    onSuggestionSelected(focusedSuggestion || {
      url: value
    });
  };

  var handleRenderSuggestions = function handleRenderSuggestions(props) {
    return renderSuggestions(_objectSpread(_objectSpread({}, props), {}, {
      instanceId: instanceId,
      withCreateSuggestion: withCreateSuggestion,
      currentInputValue: value,
      createSuggestionButtonText: createSuggestionButtonText,
      suggestionsQuery: suggestionsQuery,
      handleSuggestionClick: function handleSuggestionClick(suggestion) {
        if (props.handleSuggestionClick) {
          props.handleSuggestionClick(suggestion);
        }

        onSuggestionSelected(suggestion);
      }
    }));
  };

  var onSuggestionSelected = /*#__PURE__*/function () {
    var _ref2 = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee(selectedSuggestion) {
      var suggestion, _suggestion;

      return _regenerator.default.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              suggestion = selectedSuggestion;

              if (!(_constants.CREATE_TYPE === selectedSuggestion.type)) {
                _context.next = 12;
                break;
              }

              _context.prev = 2;
              _context.next = 5;
              return onCreateSuggestion(selectedSuggestion.title);

            case 5:
              suggestion = _context.sent;

              if ((_suggestion = suggestion) === null || _suggestion === void 0 ? void 0 : _suggestion.url) {
                onSelect(suggestion);
              }

              _context.next = 11;
              break;

            case 9:
              _context.prev = 9;
              _context.t0 = _context["catch"](2);

            case 11:
              return _context.abrupt("return");

            case 12:
              if (allowDirectEntry || suggestion && Object.keys(suggestion).length >= 1) {
                onSelect( // Some direct entries don't have types or IDs, and we still need to clear the previous ones.
                _objectSpread(_objectSpread({}, (0, _lodash.omit)(currentLink, 'id', 'url')), suggestion), suggestion);
              }

            case 13:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[2, 9]]);
    }));

    return function onSuggestionSelected(_x) {
      return _ref2.apply(this, arguments);
    };
  }();

  return (0, _element.createElement)("form", {
    onSubmit: onFormSubmit
  }, (0, _element.createElement)(_.URLInput, {
    className: className,
    value: value,
    onChange: onInputChange,
    placeholder: placeholder !== null && placeholder !== void 0 ? placeholder : (0, _i18n.__)('Search or type url'),
    __experimentalRenderSuggestions: showSuggestions ? handleRenderSuggestions : null,
    __experimentalFetchLinkSuggestions: searchHandler,
    __experimentalHandleURLSuggestions: true,
    __experimentalShowInitialSuggestions: showInitialSuggestions,
    ref: ref
  }), children);
});
var _default = LinkControlSearchInput;
exports.default = _default;
//# sourceMappingURL=search-input.js.map