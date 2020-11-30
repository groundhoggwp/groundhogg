import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

var ToolbarGroupContainer = function ToolbarGroupContainer(_ref) {
  var className = _ref.className,
      children = _ref.children,
      props = _objectWithoutProperties(_ref, ["className", "children"]);

  return createElement("div", _extends({
    className: className
  }, props), children);
};

export default ToolbarGroupContainer;
//# sourceMappingURL=toolbar-group-container.js.map