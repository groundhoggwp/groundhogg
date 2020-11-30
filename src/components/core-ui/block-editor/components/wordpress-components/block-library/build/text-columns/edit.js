"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TextColumnsEdit;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function TextColumnsEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      className = _ref.className;
  var width = attributes.width,
      content = attributes.content,
      columns = attributes.columns;
  (0, _deprecated.default)('The Text Columns block', {
    alternative: 'the Columns block',
    plugin: 'Gutenberg'
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.BlockAlignmentToolbar, {
    value: width,
    onChange: function onChange(nextWidth) {
      return setAttributes({
        width: nextWidth
      });
    },
    controls: ['center', 'wide', 'full']
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, null, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Columns'),
    value: columns,
    onChange: function onChange(value) {
      return setAttributes({
        columns: value
      });
    },
    min: 2,
    max: 4,
    required: true
  }))), (0, _element.createElement)("div", {
    className: "".concat(className, " align").concat(width, " columns-").concat(columns)
  }, (0, _lodash.times)(columns, function (index) {
    return (0, _element.createElement)("div", {
      className: "wp-block-column",
      key: "column-".concat(index)
    }, (0, _element.createElement)(_blockEditor.RichText, {
      tagName: "p",
      value: (0, _lodash.get)(content, [index, 'children']),
      onChange: function onChange(nextContent) {
        setAttributes({
          content: [].concat((0, _toConsumableArray2.default)(content.slice(0, index)), [{
            children: nextContent
          }], (0, _toConsumableArray2.default)(content.slice(index + 1)))
        });
      },
      placeholder: (0, _i18n.__)('New Column')
    }));
  })));
}
//# sourceMappingURL=edit.js.map