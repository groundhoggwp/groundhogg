"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _editor = _interopRequireDefault(require("./editor.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ColumnsPreview(_ref) {
  var columnWidths = _ref.columnWidths,
      selectedColumnIndex = _ref.selectedColumnIndex;
  var columnsPreviewStyle = (0, _compose.usePreferredColorSchemeStyle)(_editor.default.columnsPreview, _editor.default.columnsPreviewDark);
  var columnIndicatorStyle = (0, _compose.usePreferredColorSchemeStyle)(_editor.default.columnIndicator, _editor.default.columnIndicatorDark);
  return (0, _element.createElement)(_reactNative.View, {
    style: columnsPreviewStyle
  }, columnWidths.map(function (width, index) {
    var isSelectedColumn = index === selectedColumnIndex;
    return (0, _element.createElement)(_reactNative.View, {
      style: [isSelectedColumn && columnIndicatorStyle, {
        flex: width
      }],
      key: index
    });
  }));
}

var _default = ColumnsPreview;
exports.default = _default;
//# sourceMappingURL=column-preview.native.js.map