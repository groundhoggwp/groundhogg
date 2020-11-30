"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ResponsiveBlockControlLabel;

var _element = require("@wordpress/element");

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
function ResponsiveBlockControlLabel(_ref) {
  var property = _ref.property,
      viewport = _ref.viewport,
      desc = _ref.desc;
  var instanceId = (0, _compose.useInstanceId)(ResponsiveBlockControlLabel);
  var accessibleLabel = desc || (0, _i18n.sprintf)(
  /* translators: 1: property name. 2: viewport name. */
  (0, _i18n._x)('Controls the %1$s property for %2$s viewports.', 'Text labelling a interface as controlling a given layout property (eg: margin) for a given screen size.'), property, viewport.label);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("span", {
    "aria-describedby": "rbc-desc-".concat(instanceId)
  }, viewport.label), (0, _element.createElement)(_components.VisuallyHidden, {
    as: "span",
    id: "rbc-desc-".concat(instanceId)
  }, accessibleLabel));
}
//# sourceMappingURL=label.js.map