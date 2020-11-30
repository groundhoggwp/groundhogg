import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Tip } from '@wordpress/components';
var globalTips = [createInterpolateElement(__('While writing, you can press <kbd>/</kbd> to quickly insert new blocks.'), {
  kbd: createElement("kbd", null)
}), createInterpolateElement(__('Indent a list by pressing <kbd>space</kbd> at the beginning of a line.'), {
  kbd: createElement("kbd", null)
}), createInterpolateElement(__('Outdent a list by pressing <kbd>backspace</kbd> at the beginning of a line.'), {
  kbd: createElement("kbd", null)
}), __('Drag files into the editor to automatically insert media blocks.'), __("Change a block's type by pressing the block icon on the toolbar.")];

function Tips() {
  var _useState = useState( // Disable Reason: I'm not generating an HTML id.
  // eslint-disable-next-line no-restricted-syntax
  Math.floor(Math.random() * globalTips.length)),
      _useState2 = _slicedToArray(_useState, 1),
      randomIndex = _useState2[0];

  return createElement(Tip, null, globalTips[randomIndex]);
}

export default Tips;
//# sourceMappingURL=tips.js.map