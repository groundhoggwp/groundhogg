"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useResizeCanvas;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _element = require("@wordpress/element");

var _useSimulatedMediaQuery = _interopRequireDefault(require("../../components/use-simulated-media-query"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Function to resize the editor window.
 *
 * @param {string} deviceType Used for determining the size of the container (e.g. Desktop, Tablet, Mobile)
 *
 * @return {Object} Inline styles to be added to resizable container.
 */
function useResizeCanvas(deviceType) {
  var _useState = (0, _element.useState)(window.innerWidth),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      actualWidth = _useState2[0],
      updateActualWidth = _useState2[1];

  (0, _element.useEffect)(function () {
    if (deviceType === 'Desktop') {
      return;
    }

    var resizeListener = function resizeListener() {
      return updateActualWidth(window.innerWidth);
    };

    window.addEventListener('resize', resizeListener);
    return function () {
      window.removeEventListener('resize', resizeListener);
    };
  }, [deviceType]);

  var getCanvasWidth = function getCanvasWidth(device) {
    var deviceWidth;

    switch (device) {
      case 'Tablet':
        deviceWidth = 780;
        break;

      case 'Mobile':
        deviceWidth = 360;
        break;

      default:
        return null;
    }

    return deviceWidth < actualWidth ? deviceWidth : actualWidth;
  };

  var marginValue = function marginValue() {
    return window.innerHeight < 800 ? 36 : 72;
  };

  var contentInlineStyles = function contentInlineStyles(device) {
    var height = device === 'Mobile' ? '768px' : '1024px';

    switch (device) {
      case 'Tablet':
      case 'Mobile':
        return {
          width: getCanvasWidth(device),
          margin: marginValue() + 'px auto',
          flexGrow: 0,
          height: height,
          minHeight: height,
          maxHeight: height,
          overflowY: 'auto'
        };

      default:
        return null;
    }
  };

  (0, _useSimulatedMediaQuery.default)('resizable-editor-section', getCanvasWidth(deviceType));
  return contentInlineStyles(deviceType);
}
//# sourceMappingURL=index.js.map