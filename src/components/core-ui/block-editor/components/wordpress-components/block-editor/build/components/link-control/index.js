"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _dom = require("@wordpress/dom");

var _settingsDrawer = _interopRequireDefault(require("./settings-drawer"));

var _searchInput = _interopRequireDefault(require("./search-input"));

var _linkPreview = _interopRequireDefault(require("./link-preview"));

var _useCreatePage2 = _interopRequireDefault(require("./use-create-page"));

var _viewerSlot = require("./viewer-slot");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Default properties associated with a link control value.
 *
 * @typedef WPLinkControlDefaultValue
 *
 * @property {string}   url           Link URL.
 * @property {string=}  title         Link title.
 * @property {boolean=} opensInNewTab Whether link should open in a new browser
 *                                    tab. This value is only assigned if not
 *                                    providing a custom `settings` prop.
 */

/* eslint-disable jsdoc/valid-types */

/**
 * Custom settings values associated with a link.
 *
 * @typedef {{[setting:string]:any}} WPLinkControlSettingsValue
 */

/* eslint-enable */

/**
 * Custom settings values associated with a link.
 *
 * @typedef WPLinkControlSetting
 *
 * @property {string} id    Identifier to use as property for setting value.
 * @property {string} title Human-readable label to show in user interface.
 */

/**
 * Properties associated with a link control value, composed as a union of the
 * default properties and any custom settings values.
 *
 * @typedef {WPLinkControlDefaultValue&WPLinkControlSettingsValue} WPLinkControlValue
 */

/** @typedef {(nextValue:WPLinkControlValue)=>void} WPLinkControlOnChangeProp */

/**
 * Properties associated with a search suggestion used within the LinkControl.
 *
 * @typedef WPLinkControlSuggestion
 *
 * @property {string} id    Identifier to use to uniquely identify the suggestion.
 * @property {string} type  Identifies the type of the suggestion (eg: `post`,
 *                          `page`, `url`...etc)
 * @property {string} title Human-readable label to show in user interface.
 * @property {string} url   A URL for the suggestion.
 */

/** @typedef {(title:string)=>WPLinkControlSuggestion} WPLinkControlCreateSuggestionProp */

/**
 * @typedef WPLinkControlProps
 *
 * @property {(WPLinkControlSetting[])=}  settings                   An array of settings objects. Each object will used to
 *                                                                   render a `ToggleControl` for that setting.
 * @property {boolean=}                   forceIsEditingLink         If passed as either `true` or `false`, controls the
 *                                                                   internal editing state of the component to respective
 *                                                                   show or not show the URL input field.
 * @property {WPLinkControlValue=}        value                      Current link value.
 * @property {WPLinkControlOnChangeProp=} onChange                   Value change handler, called with the updated value if
 *                                                                   the user selects a new link or updates settings.
 * @property {boolean=}                   noDirectEntry              Whether to allow turning a URL-like search query directly into a link.
 * @property {boolean=}                   showSuggestions            Whether to present suggestions when typing the URL.
 * @property {boolean=}                   showInitialSuggestions     Whether to present initial suggestions immediately.
 * @property {boolean=}                   withCreateSuggestion       Whether to allow creation of link value from suggestion.
 * @property {Object=}                    suggestionsQuery           Query parameters to pass along to wp.blockEditor.__experimentalFetchLinkSuggestions.
 * @property {boolean=}                   noURLSuggestion            Whether to add a fallback suggestion which treats the search query as a URL.
 * @property {string|Function|undefined}  createSuggestionButtonText The text to use in the button that calls createSuggestion.
 */

/**
 * Renders a link control. A link control is a controlled input which maintains
 * a value associated with a link (HTML anchor element) and relevant settings
 * for how that link is expected to behave.
 *
 * @param {WPLinkControlProps} props Component props.
 */
function LinkControl(_ref) {
  var searchInputPlaceholder = _ref.searchInputPlaceholder,
      value = _ref.value,
      settings = _ref.settings,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$noDirectEntry = _ref.noDirectEntry,
      noDirectEntry = _ref$noDirectEntry === void 0 ? false : _ref$noDirectEntry,
      _ref$showSuggestions = _ref.showSuggestions,
      showSuggestions = _ref$showSuggestions === void 0 ? true : _ref$showSuggestions,
      showInitialSuggestions = _ref.showInitialSuggestions,
      forceIsEditingLink = _ref.forceIsEditingLink,
      createSuggestion = _ref.createSuggestion,
      withCreateSuggestion = _ref.withCreateSuggestion,
      _ref$inputValue = _ref.inputValue,
      propInputValue = _ref$inputValue === void 0 ? '' : _ref$inputValue,
      _ref$suggestionsQuery = _ref.suggestionsQuery,
      suggestionsQuery = _ref$suggestionsQuery === void 0 ? {} : _ref$suggestionsQuery,
      _ref$noURLSuggestion = _ref.noURLSuggestion,
      noURLSuggestion = _ref$noURLSuggestion === void 0 ? false : _ref$noURLSuggestion,
      createSuggestionButtonText = _ref.createSuggestionButtonText;

  if (withCreateSuggestion === undefined && createSuggestion) {
    withCreateSuggestion = true;
  }

  var wrapperNode = (0, _element.useRef)();

  var _useState = (0, _element.useState)(value && value.url || ''),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      internalInputValue = _useState2[0],
      setInternalInputValue = _useState2[1];

  var currentInputValue = propInputValue || internalInputValue;

  var _useState3 = (0, _element.useState)(forceIsEditingLink !== undefined ? forceIsEditingLink : !value || !value.url),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isEditingLink = _useState4[0],
      setIsEditingLink = _useState4[1];

  var isEndingEditWithFocus = (0, _element.useRef)(false);
  (0, _element.useEffect)(function () {
    if (forceIsEditingLink !== undefined && forceIsEditingLink !== isEditingLink) {
      setIsEditingLink(forceIsEditingLink);
    }
  }, [forceIsEditingLink]);
  (0, _element.useEffect)(function () {
    // When `isEditingLink` is set to `false`, a focus loss could occur
    // since the link input may be removed from the DOM. To avoid this,
    // reinstate focus to a suitable target if focus has in-fact been lost.
    // Note that the check is necessary because while typically unsetting
    // edit mode would render the read-only mode's link element, it isn't
    // guaranteed. The link input may continue to be shown if the next value
    // is still unassigned after calling `onChange`.
    var hadFocusLoss = isEndingEditWithFocus.current && wrapperNode.current && !wrapperNode.current.contains(wrapperNode.current.ownerDocument.activeElement);

    if (hadFocusLoss) {
      // Prefer to focus a natural focusable descendent of the wrapper,
      // but settle for the wrapper if there are no other options.
      var nextFocusTarget = _dom.focus.focusable.find(wrapperNode.current)[0] || wrapperNode.current;
      nextFocusTarget.focus();
    }

    isEndingEditWithFocus.current = false;
  }, [isEditingLink]);
  /**
   * Cancels editing state and marks that focus may need to be restored after
   * the next render, if focus was within the wrapper when editing finished.
   */

  function stopEditing() {
    var _wrapperNode$current;

    isEndingEditWithFocus.current = !!((_wrapperNode$current = wrapperNode.current) === null || _wrapperNode$current === void 0 ? void 0 : _wrapperNode$current.contains(wrapperNode.current.ownerDocument.activeElement));
    setIsEditingLink(false);
  }

  var _useCreatePage = (0, _useCreatePage2.default)(createSuggestion),
      createPage = _useCreatePage.createPage,
      isCreatingPage = _useCreatePage.isCreatingPage,
      errorMessage = _useCreatePage.errorMessage;

  var handleSelectSuggestion = function handleSelectSuggestion(updatedValue) {
    onChange(updatedValue);
    stopEditing();
  };

  return (0, _element.createElement)("div", {
    tabIndex: -1,
    ref: wrapperNode,
    className: "block-editor-link-control"
  }, isCreatingPage && (0, _element.createElement)("div", {
    className: "block-editor-link-control__loading"
  }, (0, _element.createElement)(_components.Spinner, null), " ", (0, _i18n.__)('Creating'), "\u2026"), (isEditingLink || !value) && !isCreatingPage && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: "block-editor-link-control__search-input-wrapper"
  }, (0, _element.createElement)(_searchInput.default, {
    currentLink: value,
    className: "block-editor-link-control__search-input",
    placeholder: searchInputPlaceholder,
    value: currentInputValue,
    withCreateSuggestion: withCreateSuggestion,
    onCreateSuggestion: createPage,
    onChange: setInternalInputValue,
    onSelect: handleSelectSuggestion,
    showInitialSuggestions: showInitialSuggestions,
    allowDirectEntry: !noDirectEntry,
    showSuggestions: showSuggestions,
    suggestionsQuery: suggestionsQuery,
    withURLSuggestion: !noURLSuggestion,
    createSuggestionButtonText: createSuggestionButtonText
  }, (0, _element.createElement)("div", {
    className: "block-editor-link-control__search-actions"
  }, (0, _element.createElement)(_components.Button, {
    type: "submit",
    label: (0, _i18n.__)('Submit'),
    icon: _icons.keyboardReturn,
    className: "block-editor-link-control__search-submit"
  })))), errorMessage && (0, _element.createElement)(_components.Notice, {
    className: "block-editor-link-control__search-error",
    status: "error",
    isDismissible: false
  }, errorMessage)), value && !isEditingLink && !isCreatingPage && (0, _element.createElement)(_linkPreview.default, {
    value: value,
    onEditClick: function onEditClick() {
      return setIsEditingLink(true);
    }
  }), (0, _element.createElement)(_settingsDrawer.default, {
    value: value,
    settings: settings,
    onChange: onChange
  }));
}

LinkControl.ViewerFill = _viewerSlot.ViewerFill;
var _default = LinkControl;
exports.default = _default;
//# sourceMappingURL=index.js.map