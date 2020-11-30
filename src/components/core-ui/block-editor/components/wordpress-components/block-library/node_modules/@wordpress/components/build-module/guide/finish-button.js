import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useRef, useLayoutEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */

import Button from '../button';
export default function FinishButton(_ref) {
  var className = _ref.className,
      onClick = _ref.onClick,
      children = _ref.children;
  var button = useRef(null); // Focus the button on mount if nothing else is focused. This prevents a
  // focus loss when the 'Next' button is swapped out.

  useLayoutEffect(function () {
    if (!document.activeElement || document.activeElement === document.body) {
      button.current.focus();
    }
  }, [button]);
  return createElement(Button, {
    ref: button,
    className: className,
    isPrimary: true,
    onClick: onClick
  }, children);
}
//# sourceMappingURL=finish-button.js.map