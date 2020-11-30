"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _editor = _interopRequireDefault(require("./editor.scss"));

var _columnPreview = _interopRequireDefault(require("./column-preview"));

var _utils = require("../columns/utils");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function ColumnEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      hasChildren = _ref.hasChildren,
      isSelected = _ref.isSelected,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      isParentSelected = _ref.isParentSelected,
      contentStyle = _ref.contentStyle,
      columns = _ref.columns,
      columnCount = _ref.columnCount,
      selectedColumnIndex = _ref.selectedColumnIndex,
      parentAlignment = _ref.parentAlignment;
  var verticalAlignment = attributes.verticalAlignment;

  var updateAlignment = function updateAlignment(alignment) {
    setAttributes({
      verticalAlignment: alignment
    });
  };

  (0, _element.useEffect)(function () {
    if (!verticalAlignment && parentAlignment) {
      updateAlignment(parentAlignment);
    }
  }, []);

  var onWidthChange = function onWidthChange(width) {
    setAttributes({
      width: width
    });
  };

  var columnWidths = Object.values((0, _utils.getColumnWidths)(columns, columnCount));

  if (!isSelected && !hasChildren) {
    return (0, _element.createElement)(_reactNative.View, {
      style: [!isParentSelected && getStylesFromColorScheme(_editor.default.columnPlaceholder, _editor.default.columnPlaceholderDark), contentStyle, _editor.default.columnPlaceholderNotSelected]
    });
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Column settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Width'),
    min: 1,
    max: 100,
    value: columnWidths[selectedColumnIndex],
    onChange: onWidthChange,
    decimalNum: 1,
    rangePreview: (0, _element.createElement)(_columnPreview.default, {
      columnWidths: columnWidths,
      selectedColumnIndex: selectedColumnIndex
    })
  })), (0, _element.createElement)(_components.PanelBody, null, (0, _element.createElement)(_components.FooterMessageControl, {
    label: (0, _i18n.__)('Note: Column layout may vary between themes and screen sizes')
  }))), (0, _element.createElement)(_reactNative.View, {
    style: [contentStyle, isSelected && hasChildren && _editor.default.innerBlocksBottomSpace]
  }, (0, _element.createElement)(_blockEditor.InnerBlocks, {
    renderAppender: isSelected && _blockEditor.InnerBlocks.ButtonBlockAppender
  })));
}

function ColumnEditWrapper(props) {
  var verticalAlignment = props.attributes.verticalAlignment;

  var getVerticalAlignmentRemap = function getVerticalAlignmentRemap(alignment) {
    if (!alignment) return _editor.default.flexBase;
    return _objectSpread(_objectSpread({}, _editor.default.flexBase), _editor.default["is-vertically-aligned-".concat(alignment)]);
  };

  return (0, _element.createElement)(_reactNative.View, {
    style: getVerticalAlignmentRemap(verticalAlignment)
  }, (0, _element.createElement)(ColumnEdit, props));
}

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
  var _getBlockAttributes;

  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockRootClientId = _select.getBlockRootClientId,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlocks = _select.getBlocks,
      getBlockOrder = _select.getBlockOrder,
      getBlockAttributes = _select.getBlockAttributes;

  var selectedBlockClientId = getSelectedBlockClientId();
  var isSelected = selectedBlockClientId === clientId;
  var parentId = getBlockRootClientId(clientId);
  var hasChildren = !!getBlockCount(clientId);
  var isParentSelected = selectedBlockClientId && selectedBlockClientId === parentId;
  var blockOrder = getBlockOrder(parentId);
  var selectedColumnIndex = blockOrder.indexOf(clientId);
  var columnCount = getBlockCount(parentId);
  var columns = getBlocks(parentId);
  var parentAlignment = (_getBlockAttributes = getBlockAttributes(parentId)) === null || _getBlockAttributes === void 0 ? void 0 : _getBlockAttributes.verticalAlignment;
  return {
    hasChildren: hasChildren,
    isParentSelected: isParentSelected,
    isSelected: isSelected,
    selectedColumnIndex: selectedColumnIndex,
    columns: columns,
    columnCount: columnCount,
    parentAlignment: parentAlignment
  };
}), _compose.withPreferredColorScheme])(ColumnEditWrapper);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map