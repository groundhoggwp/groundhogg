import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
/**
 * External dependencies
 */

import { map } from 'lodash';
import memoize from 'memize';
/**
 * Internal dependencies
 */

import { About, Blog, Contact, Portfolio, Services, Team } from './templates';
var defaultTemplates = [About, Blog, Contact, Portfolio, Services, Team];

var createInnerBlocks = function createInnerBlocks(_ref) {
  var name = _ref.name,
      attributes = _ref.attributes,
      innerBlocks = _ref.innerBlocks;
  return createBlock(name, attributes, map(innerBlocks, createInnerBlocks));
};

var createBlocks = function createBlocks(template) {
  return template.map(function (_ref2) {
    var name = _ref2.name,
        attributes = _ref2.attributes,
        innerBlocks = _ref2.innerBlocks;
    return createBlock(name, attributes, map(innerBlocks, createInnerBlocks));
  });
};

var parsedTemplates = memoize(function () {
  return defaultTemplates.map(function (template) {
    return _objectSpread(_objectSpread({}, template), {}, {
      blocks: createBlocks(template.content)
    });
  });
});
export default parsedTemplates;
//# sourceMappingURL=default-templates.native.js.map