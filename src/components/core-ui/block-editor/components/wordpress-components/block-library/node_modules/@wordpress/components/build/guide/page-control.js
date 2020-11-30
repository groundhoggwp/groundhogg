"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PageControl;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _button = _interopRequireDefault(require("../button"));

var _icons = require("./icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PageControl(_ref) {
  var currentPage = _ref.currentPage,
      numberOfPages = _ref.numberOfPages,
      setCurrentPage = _ref.setCurrentPage;
  return (0, _element.createElement)("ul", {
    className: "components-guide__page-control",
    "aria-label": (0, _i18n.__)('Guide controls')
  }, (0, _lodash.times)(numberOfPages, function (page) {
    return (0, _element.createElement)("li", {
      key: page // Set aria-current="step" on the active page, see https://www.w3.org/TR/wai-aria-1.1/#aria-current
      ,
      "aria-current": page === currentPage ? 'step' : undefined
    }, (0, _element.createElement)(_button.default, {
      key: page,
      icon: (0, _element.createElement)(_icons.PageControlIcon, {
        isSelected: page === currentPage
      }),
      "aria-label": (0, _i18n.sprintf)(
      /* translators: 1: current page number 2: total number of pages */
      (0, _i18n.__)('Page %1$d of %2$d'), page + 1, numberOfPages),
      onClick: function onClick() {
        return setCurrentPage(page);
      }
    }));
  }));
}
//# sourceMappingURL=page-control.js.map