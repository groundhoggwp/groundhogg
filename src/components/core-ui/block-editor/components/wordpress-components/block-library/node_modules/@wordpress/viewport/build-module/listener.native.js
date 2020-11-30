/**
 * External dependencies
 */
import { forEach, reduce } from 'lodash';
import { Dimensions } from 'react-native';
/**
 * WordPress dependencies
 */

import { dispatch } from '@wordpress/data';

var matchWidth = function matchWidth(operator, breakpoint) {
  var _Dimensions$get = Dimensions.get('window'),
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
    var matches = reduce(breakpoints, function (result, width, name) {
      forEach(operators, function (condition, operator) {
        var key = [operator, name].join(' ');
        result[key] = matchWidth(condition, width);
      });
      return result;
    }, {});
    dispatch('core/viewport').setIsMatching(matches);
  };

  Dimensions.addEventListener('change', setIsMatching); // Set initial values

  setIsMatching();
};

export default addDimensionsEventListener;
//# sourceMappingURL=listener.native.js.map