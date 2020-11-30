import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { ToolbarButton, SVG, Path, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockNavigationList from './block-navigation-list';
var NavigatorIcon = createElement(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  width: "24",
  height: "24"
}, createElement(Path, {
  d: "M13.8 5.2H3v1.5h10.8V5.2zm-3.6 12v1.5H21v-1.5H10.2zm7.2-6H6.6v1.5h10.8v-1.5z"
}));
export default function useBlockNavigator(clientId, __experimentalFeatures) {
  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isNavigationListOpen = _useState2[0],
      setIsNavigationListOpen = _useState2[1];

  var navigatorToolbarButton = createElement(ToolbarButton, {
    className: "components-toolbar__control",
    label: __('Open block navigator'),
    onClick: function onClick() {
      return setIsNavigationListOpen(true);
    },
    icon: NavigatorIcon
  });
  var navigatorModal = isNavigationListOpen && createElement(Modal, {
    title: __('Block Navigator'),
    closeLabel: __('Close'),
    onRequestClose: function onRequestClose() {
      setIsNavigationListOpen(false);
    }
  }, createElement(BlockNavigationList, {
    clientId: clientId,
    __experimentalFeatures: __experimentalFeatures
  }));
  return {
    navigatorToolbarButton: navigatorToolbarButton,
    navigatorModal: navigatorModal
  };
}
//# sourceMappingURL=use-block-navigator.js.map