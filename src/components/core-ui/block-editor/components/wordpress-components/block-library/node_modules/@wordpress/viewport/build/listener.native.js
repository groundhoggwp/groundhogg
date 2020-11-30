"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var matchWidth = function matchWidth(operator, breakpoint) {
  var _Dimensions$get = _reactNative.Dimensions.get('window'),
      width = _Dimensions$get.width;

  if (operator === 'max-width') {
    return width < breakpoint;
  } else if (operator === 'min-width') {
    return width >= breakpoint;
  }

  throw new Error("Unsupported viewport operator: ".concat(operator));
};

var addDimensionsEventListener = function addDimensionsEventListener(breakpoints, operators) {
  var setIsMatching = function setIsMatching() {
    var matches = (0, _lodash.reduce)(breakpoints, function (result, width, name) {
      (0, _lodash.forEach)(operators, function (condition, operator) {
        var key = [operator, name].join(' ');
        result[key] = matchWidth(condition, width);
      });
      return result;
    }, {});
    (0, _data.dispatch)('core/viewport').setIsMatching(matches);
  };

  _reactNative.Dimensions.addEventListener('change', setIsMatching); // Set initial values


  setIsMatching();
};

var _default = addDimensionsEventListener;
exports.default = _default;
//# sourceMappingURL=listener.native.js.map