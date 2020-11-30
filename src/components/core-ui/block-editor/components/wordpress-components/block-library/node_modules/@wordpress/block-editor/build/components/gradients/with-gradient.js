"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.withGradient = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _useGradient = require("./use-gradient");

/**
 * Internal dependencies
 */
var withGradient = function withGradient(WrappedComponent) {
  return function (props) {
    var _experimentalUseGrad = (0, _useGradient.__experimentalUseGradient)(),
        gradientValue = _experimentalUseGrad.gradientValue;

    return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
      gradientValue: gradientValue
    }));
  };
};

exports.withGradient = withGradient;
//# sourceMappingURL=with-gradient.js.map