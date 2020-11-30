"use strict";

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

var _data = require("@wordpress/data");

var _reducer = _interopRequireDefault(require("./reducer"));

var selectors = _interopRequireWildcard(require("./selectors"));

var actions = _interopRequireWildcard(require("./actions"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
(0, _data.registerStore)('core/rich-text', {
  reducer: _reducer.default,
  selectors: selectors,
  actions: actions
});
//# sourceMappingURL=index.js.map