"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _compose = require("@wordpress/compose");

var _context = require("../block-edit/context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var withClientId = (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
  return function (props) {
    var _useBlockEditContext = (0, _context.useBlockEditContext)(),
        clientId = _useBlockEditContext.clientId;

    return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
      clientId: clientId
    }));
  };
}, 'withClientId');
var _default = withClientId;
exports.default = _default;
//# sourceMappingURL=with-client-id.js.map