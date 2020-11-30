import { createElement } from "@wordpress/element";

function PanelHeader(_ref) {
  var label = _ref.label,
      children = _ref.children;
  return createElement("div", {
    className: "components-panel__header"
  }, label && createElement("h2", null, label), children);
}

export default PanelHeader;
//# sourceMappingURL=header.js.map