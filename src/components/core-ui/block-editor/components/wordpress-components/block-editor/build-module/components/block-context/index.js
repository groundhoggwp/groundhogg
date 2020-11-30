import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { createContext, useContext, useMemo } from '@wordpress/element';
/** @typedef {import('react').ReactNode} ReactNode */

/**
 * @typedef BlockContextProviderProps
 *
 * @property {Record<string,*>} value    Context value to merge with current
 *                                       value.
 * @property {ReactNode}        children Component children.
 */

/** @type {import('react').Context<Record<string,*>>} */

var Context = createContext({});
/**
 * Component which merges passed value with current consumed block context.
 *
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-context/README.md
 *
 * @param {BlockContextProviderProps} props
 */

export function BlockContextProvider(_ref) {
  var value = _ref.value,
      children = _ref.children;
  var context = useContext(Context);
  var nextValue = useMemo(function () {
    return _objectSpread(_objectSpread({}, context), value);
  }, [context, value]);
  return createElement(Context.Provider, {
    value: nextValue,
    children: children
  });
}
export default Context;
//# sourceMappingURL=index.js.map