"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useClientWidth;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
function useClientWidth(ref, dependencies) {
  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      clientWidth = _useState2[0],
      setClientWidth = _useState2[1];

  function calculateClientWidth() {
    setClientWidth(ref.current.clientWidth);
  }

  (0, _element.useEffect)(calculateClientWidth, dependencies);
  (0, _element.useEffect)(function () {
    var defaultView = ref.current.ownerDocument.defaultView;
    defaultView.addEventListener('resize', calculateClientWidth);
    return function () {
      defaultView.removeEventListener('resize', calculateClientWidth);
    };
  }, []);
  return clientWidth;
}
//# sourceMappingURL=use-client-width.js.map