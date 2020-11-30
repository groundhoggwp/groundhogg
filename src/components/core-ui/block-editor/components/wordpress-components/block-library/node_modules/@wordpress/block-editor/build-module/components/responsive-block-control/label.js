import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { VisuallyHidden } from '@wordpress/components';
import { _x, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
export default function ResponsiveBlockControlLabel(_ref) {
  var property = _ref.property,
      viewport = _ref.viewport,
      desc = _ref.desc;
  var instanceId = useInstanceId(ResponsiveBlockControlLabel);
  var accessibleLabel = desc || sprintf(
  /* translators: 1: property name. 2: viewport name. */
  _x('Controls the %1$s property for %2$s viewports.', 'Text labelling a interface as controlling a given layout property (eg: margin) for a given screen size.'), property, viewport.label);
  return createElement(Fragment, null, createElement("span", {
    "aria-describedby": "rbc-desc-".concat(instanceId)
  }, viewport.label), createElement(VisuallyHidden, {
    as: "span",
    id: "rbc-desc-".concat(instanceId)
  }, accessibleLabel));
}
//# sourceMappingURL=label.js.map