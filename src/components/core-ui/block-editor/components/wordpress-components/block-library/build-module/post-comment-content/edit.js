import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useEntityProp } from '@wordpress/core-data'; // TODO: JSDOC types

export default function Edit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context;
  var className = attributes.className;
  var commentId = context.commentId;

  var _useEntityProp = useEntityProp('root', 'comment', 'content', commentId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      content = _useEntityProp2[0];

  return createElement("p", {
    className: className
  }, content);
}
//# sourceMappingURL=edit.js.map