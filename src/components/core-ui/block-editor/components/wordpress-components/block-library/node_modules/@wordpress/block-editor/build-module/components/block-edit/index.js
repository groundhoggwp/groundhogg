import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import Edit from './edit';
import { BlockEditContextProvider, useBlockEditContext } from './context';
export { useBlockEditContext };
export default function BlockEdit(props) {
  var name = props.name,
      isSelected = props.isSelected,
      clientId = props.clientId,
      onFocus = props.onFocus,
      onCaretVerticalPositionChange = props.onCaretVerticalPositionChange;
  var context = {
    name: name,
    isSelected: isSelected,
    clientId: clientId,
    onFocus: onFocus,
    onCaretVerticalPositionChange: onCaretVerticalPositionChange
  };
  return createElement(BlockEditContextProvider // It is important to return the same object if props haven't
  // changed to avoid  unnecessary rerenders.
  // See https://reactjs.org/docs/context.html#caveats.
  , {
    value: useMemo(function () {
      return context;
    }, Object.values(context))
  }, createElement(Edit, props));
}
//# sourceMappingURL=index.js.map