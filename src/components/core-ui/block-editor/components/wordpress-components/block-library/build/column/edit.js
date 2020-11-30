"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function ColumnEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      verticalAlignment = _ref$attributes.verticalAlignment,
      width = _ref$attributes.width,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId;
  var classes = (0, _classnames2.default)('block-core-columns', (0, _defineProperty2.default)({}, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment));

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockOrder = _select.getBlockOrder,
        getBlockRootClientId = _select.getBlockRootClientId;

    return {
      hasChildBlocks: getBlockOrder(clientId).length > 0,
      rootClientId: getBlockRootClientId(clientId)
    };
  }, [clientId]),
      hasChildBlocks = _useSelect.hasChildBlocks,
      rootClientId = _useSelect.rootClientId;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var updateAlignment = function updateAlignment(value) {
    // Update own alignment.
    setAttributes({
      verticalAlignment: value
    }); // Reset parent Columns block.

    updateBlockAttributes(rootClientId, {
      verticalAlignment: null
    });
  };

  var hasWidth = Number.isFinite(width);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: classes,
    style: hasWidth ? {
      flexBasis: width + '%'
    } : undefined
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Column settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Percentage width'),
    value: width || '',
    onChange: function onChange(nextWidth) {
      setAttributes({
        width: nextWidth
      });
    },
    min: 0,
    max: 100,
    step: 0.1,
    required: true,
    allowReset: true,
    placeholder: width === undefined ? (0, _i18n.__)('Auto') : undefined
  }))), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    templateLock: false,
    renderAppender: hasChildBlocks ? undefined : _blockEditor.InnerBlocks.ButtonBlockAppender,
    __experimentalTagName: "div",
    __experimentalPassedProps: blockWrapperProps
  }));
}

var _default = ColumnEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map