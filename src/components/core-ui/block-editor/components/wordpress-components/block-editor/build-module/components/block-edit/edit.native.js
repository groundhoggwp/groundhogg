import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { withFilters } from '@wordpress/components';
import { getBlockType } from '@wordpress/blocks';
export var Edit = function Edit(props) {
  var name = props.name;
  var blockType = getBlockType(name);

  if (!blockType) {
    return null;
  }

  var Component = blockType.edit;
  return createElement(Component, props);
};
export default withFilters('editor.BlockEdit')(Edit);
//# sourceMappingURL=edit.native.js.map