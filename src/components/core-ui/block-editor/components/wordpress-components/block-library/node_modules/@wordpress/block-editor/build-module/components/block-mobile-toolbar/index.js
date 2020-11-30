import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useViewportMatch } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BlockMover from '../block-mover';

function BlockMobileToolbar(_ref) {
  var clientId = _ref.clientId;
  var isMobile = useViewportMatch('small', '<');

  if (!isMobile) {
    return null;
  }

  return createElement("div", {
    className: "block-editor-block-mobile-toolbar"
  }, createElement(BlockMover, {
    clientIds: [clientId]
  }));
}

export default BlockMobileToolbar;
//# sourceMappingURL=index.js.map