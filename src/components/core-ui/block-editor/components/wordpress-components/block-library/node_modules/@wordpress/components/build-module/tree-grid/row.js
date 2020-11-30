import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

function TreeGridRow(_ref, ref) {
  var children = _ref.children,
      level = _ref.level,
      positionInSet = _ref.positionInSet,
      setSize = _ref.setSize,
      isExpanded = _ref.isExpanded,
      props = _objectWithoutProperties(_ref, ["children", "level", "positionInSet", "setSize", "isExpanded"]);

  return (// Disable reason: Due to an error in the ARIA 1.1 specification, the
    // aria-posinset and aria-setsize properties are not supported on row
    // elements. This is being corrected in ARIA 1.2. Consequently, the
    // linting rule fails when validating this markup.
    //
    // eslint-disable-next-line jsx-a11y/role-supports-aria-props
    createElement("tr", _extends({}, props, {
      ref: ref,
      role: "row",
      "aria-level": level,
      "aria-posinset": positionInSet,
      "aria-setsize": setSize,
      "aria-expanded": isExpanded
    }), children)
  );
}

export default forwardRef(TreeGridRow);
//# sourceMappingURL=row.js.map