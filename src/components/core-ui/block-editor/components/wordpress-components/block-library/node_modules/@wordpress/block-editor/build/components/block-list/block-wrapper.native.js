"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useBlockWrapperProps = useBlockWrapperProps;
exports.Block = void 0;

var _blockWrapperElements = _interopRequireDefault(require("./block-wrapper-elements"));

/**
 * Internal dependencies
 */
function useBlockWrapperProps() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return props;
}

var ExtendedBlockComponent = _blockWrapperElements.default.reduce(function (acc, element) {
  acc[element] = element;
  return acc;
}, String);

var Block = ExtendedBlockComponent;
exports.Block = Block;
//# sourceMappingURL=block-wrapper.native.js.map