import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { getBlockDOMNode } from '../../utils/dom';

var SkipToSelectedBlock = function SkipToSelectedBlock(_ref) {
  var selectedBlockClientId = _ref.selectedBlockClientId;

  var onClick = function onClick() {
    var selectedBlockElement = getBlockDOMNode(selectedBlockClientId);
    selectedBlockElement.focus();
  };

  return selectedBlockClientId && createElement(Button, {
    isSecondary: true,
    className: "block-editor-skip-to-selected-block",
    onClick: onClick
  }, __('Skip to the selected block'));
};

export default withSelect(function (select) {
  return {
    selectedBlockClientId: select('core/block-editor').getBlockSelectionStart()
  };
})(SkipToSelectedBlock);
//# sourceMappingURL=index.js.map