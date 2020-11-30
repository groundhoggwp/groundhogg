import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { close } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import Button from '../button';

var ModalHeader = function ModalHeader(_ref) {
  var icon = _ref.icon,
      title = _ref.title,
      onClose = _ref.onClose,
      closeLabel = _ref.closeLabel,
      headingId = _ref.headingId,
      isDismissible = _ref.isDismissible;
  var label = closeLabel ? closeLabel : __('Close dialog');
  return createElement("div", {
    className: "components-modal__header"
  }, createElement("div", {
    className: "components-modal__header-heading-container"
  }, icon && createElement("span", {
    className: "components-modal__icon-container",
    "aria-hidden": true
  }, icon), title && createElement("h1", {
    id: headingId,
    className: "components-modal__header-heading"
  }, title)), isDismissible && createElement(Button, {
    onClick: onClose,
    icon: close,
    label: label
  }));
};

export default ModalHeader;
//# sourceMappingURL=header.js.map