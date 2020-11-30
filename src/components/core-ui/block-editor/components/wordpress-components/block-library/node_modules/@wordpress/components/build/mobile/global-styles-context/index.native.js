"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.withGlobalStyles = exports.useGlobalStyles = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

/**
 * WordPress dependencies
 */
var GlobalStylesContext = (0, _element.createContext)({
  style: {}
});

var useGlobalStyles = function useGlobalStyles() {
  var globalStyles = (0, _element.useContext)(GlobalStylesContext);
  return globalStyles;
};

exports.useGlobalStyles = useGlobalStyles;

var withGlobalStyles = function withGlobalStyles(WrappedComponent) {
  return function (props) {
    return (0, _element.createElement)(GlobalStylesContext.Consumer, null, function (globalStyles) {
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, {
        globalStyles: globalStyles
      }));
    });
  };
};

exports.withGlobalStyles = withGlobalStyles;
var _default = GlobalStylesContext;
exports.default = _default;
//# sourceMappingURL=index.native.js.map