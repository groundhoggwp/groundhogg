import { createElement } from "@wordpress/element";

var ToolbarButtonContainer = function ToolbarButtonContainer(props) {
  return createElement("div", {
    className: props.className
  }, props.children);
};

export default ToolbarButtonContainer;
//# sourceMappingURL=toolbar-button-container.js.map