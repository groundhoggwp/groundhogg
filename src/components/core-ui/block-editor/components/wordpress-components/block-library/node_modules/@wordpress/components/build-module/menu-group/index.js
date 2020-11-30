import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Children } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
export function MenuGroup(_ref) {
  var children = _ref.children,
      _ref$className = _ref.className,
      className = _ref$className === void 0 ? '' : _ref$className,
      label = _ref.label;
  var instanceId = useInstanceId(MenuGroup);

  if (!Children.count(children)) {
    return null;
  }

  var labelId = "components-menu-group-label-".concat(instanceId);
  var classNames = classnames(className, 'components-menu-group');
  return createElement("div", {
    className: classNames
  }, label && createElement("div", {
    className: "components-menu-group__label",
    id: labelId,
    "aria-hidden": "true"
  }, label), createElement("div", {
    role: "group",
    "aria-labelledby": label ? labelId : null
  }, children));
}
export default MenuGroup;
//# sourceMappingURL=index.js.map