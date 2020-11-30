"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Guide;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

var _i18n = require("@wordpress/i18n");

var _modal = _interopRequireDefault(require("../modal"));

var _keyboardShortcuts = _interopRequireDefault(require("../keyboard-shortcuts"));

var _button = _interopRequireDefault(require("../button"));

var _pageControl = _interopRequireDefault(require("./page-control"));

var _finishButton = _interopRequireDefault(require("./finish-button"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Guide(_ref) {
  var children = _ref.children,
      className = _ref.className,
      contentLabel = _ref.contentLabel,
      finishButtonText = _ref.finishButtonText,
      onFinish = _ref.onFinish,
      _ref$pages = _ref.pages,
      pages = _ref$pages === void 0 ? [] : _ref$pages;

  var _useState = (0, _element.useState)(0),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      currentPage = _useState2[0],
      setCurrentPage = _useState2[1];

  (0, _element.useEffect)(function () {
    if (_element.Children.count(children)) {
      (0, _deprecated.default)('Passing children to <Guide>', {
        alternative: 'the `pages` prop'
      });
    }
  }, [children]);

  if (_element.Children.count(children)) {
    pages = _element.Children.map(children, function (child) {
      return {
        content: child
      };
    });
  }

  var canGoBack = currentPage > 0;
  var canGoForward = currentPage < pages.length - 1;

  var goBack = function goBack() {
    if (canGoBack) {
      setCurrentPage(currentPage - 1);
    }
  };

  var goForward = function goForward() {
    if (canGoForward) {
      setCurrentPage(currentPage + 1);
    }
  };

  if (pages.length === 0) {
    return null;
  }

  return (0, _element.createElement)(_modal.default, {
    className: (0, _classnames.default)('components-guide', className),
    contentLabel: contentLabel,
    onRequestClose: onFinish
  }, (0, _element.createElement)(_keyboardShortcuts.default, {
    key: currentPage,
    shortcuts: {
      left: goBack,
      right: goForward
    }
  }), (0, _element.createElement)("div", {
    className: "components-guide__container"
  }, (0, _element.createElement)("div", {
    className: "components-guide__page"
  }, pages[currentPage].image, (0, _element.createElement)(_pageControl.default, {
    currentPage: currentPage,
    numberOfPages: pages.length,
    setCurrentPage: setCurrentPage
  }), pages[currentPage].content, !canGoForward && (0, _element.createElement)(_finishButton.default, {
    className: "components-guide__inline-finish-button",
    onClick: onFinish
  }, finishButtonText || (0, _i18n.__)('Finish'))), (0, _element.createElement)("div", {
    className: "components-guide__footer"
  }, canGoBack && (0, _element.createElement)(_button.default, {
    className: "components-guide__back-button",
    onClick: goBack
  }, (0, _i18n.__)('Previous')), canGoForward && (0, _element.createElement)(_button.default, {
    className: "components-guide__forward-button",
    onClick: goForward
  }, (0, _i18n.__)('Next')), !canGoForward && (0, _element.createElement)(_finishButton.default, {
    className: "components-guide__finish-button",
    onClick: onFinish
  }, finishButtonText || (0, _i18n.__)('Finish')))));
}
//# sourceMappingURL=index.js.map