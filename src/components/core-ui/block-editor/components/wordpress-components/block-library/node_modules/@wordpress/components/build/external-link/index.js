"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ExternalLink = ExternalLink;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _visuallyHidden = _interopRequireDefault(require("../visually-hidden"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ExternalLink(_ref, ref) {
  var href = _ref.href,
      children = _ref.children,
      className = _ref.className,
      _ref$rel = _ref.rel,
      rel = _ref$rel === void 0 ? '' : _ref$rel,
      additionalProps = (0, _objectWithoutProperties2.default)(_ref, ["href", "children", "className", "rel"]);
  rel = (0, _lodash.uniq)((0, _lodash.compact)([].concat((0, _toConsumableArray2.default)(rel.split(' ')), ['external', 'noreferrer', 'noopener']))).join(' ');
  var classes = (0, _classnames.default)('components-external-link', className);
  return (0, _element.createElement)("a", (0, _extends2.default)({}, additionalProps, {
    className: classes,
    href: href // eslint-disable-next-line react/jsx-no-target-blank
    ,
    target: "_blank",
    rel: rel,
    ref: ref
  }), children, (0, _element.createElement)(_visuallyHidden.default, {
    as: "span"
  },
  /* translators: accessibility text */
  (0, _i18n.__)('(opens in a new tab)')), (0, _element.createElement)(_icons.Icon, {
    icon: _icons.external,
    className: "components-external-link__icon"
  }));
}

var _default = (0, _element.forwardRef)(ExternalLink);

exports.default = _default;
//# sourceMappingURL=index.js.map