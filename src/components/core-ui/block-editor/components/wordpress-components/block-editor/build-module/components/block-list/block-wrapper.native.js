/**
 * Internal dependencies
 */
import ELEMENTS from './block-wrapper-elements';
export function useBlockWrapperProps() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return props;
}
var ExtendedBlockComponent = ELEMENTS.reduce(function (acc, element) {
  acc[element] = element;
  return acc;
}, String);
export var Block = ExtendedBlockComponent;
//# sourceMappingURL=block-wrapper.native.js.map