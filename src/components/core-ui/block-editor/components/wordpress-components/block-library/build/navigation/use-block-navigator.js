"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useBlockNavigator;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _blockNavigationList = _interopRequireDefault(require("./block-navigation-list"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var NavigatorIcon = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  width: "24",
  height: "24"
}, (0, _element.createElement)(_components.Path, {
  d: "M13.8 5.2H3v1.5h10.8V5.2zm-3.6 12v1.5H21v-1.5H10.2zm7.2-6H6.6v1.5h10.8v-1.5z"
}));

function useBlockNavigator(clientId, __experimentalFeatures) {
  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isNavigationListOpen = _useState2[0],
      setIsNavigationListOpen = _useState2[1];

  var navigatorToolbarButton = (0, _element.createElement)(_components.ToolbarButton, {
    className: "components-toolbar__control",
    label: (0, _i18n.__)('Open block navigator'),
    onClick: function onClick() {
      return setIsNavigationListOpen(true);
    },
    icon: NavigatorIcon
  });
  var navigatorModal = isNavigationListOpen && (0, _element.createElement)(_components.Modal, {
    title: (0, _i18n.__)('Block Navigator'),
    closeLabel: (0, _i18n.__)('Close'),
    onRequestClose: function onRequestClose() {
      setIsNavigationListOpen(false);
    }
  }, (0, _element.createElement)(_blockNavigationList.default, {
    clientId: clientId,
    __experimentalFeatures: __experimentalFeatures
  }));
  return {
    navigatorToolbarButton: navigatorToolbarButton,
    navigatorModal: navigatorModal
  };
}
//# sourceMappingURL=use-block-navigator.js.map