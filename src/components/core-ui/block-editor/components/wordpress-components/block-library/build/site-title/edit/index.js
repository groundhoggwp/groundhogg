"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = SiteTitleEdit;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _coreData = require("@wordpress/core-data");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _levelToolbar = _interopRequireDefault(require("./level-toolbar"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function SiteTitleEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var level = attributes.level,
      textAlign = attributes.textAlign;

  var _useEntityProp = (0, _coreData.useEntityProp)('root', 'site', 'title'),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 2),
      title = _useEntityProp2[0],
      setTitle = _useEntityProp2[1];

  var tagName = level === 0 ? 'p' : "h".concat(level);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  }), (0, _element.createElement)(_levelToolbar.default, {
    level: level,
    onChange: function onChange(newLevel) {
      return setAttributes({
        level: newLevel
      });
    }
  })), (0, _element.createElement)(_blockEditor.RichText, (0, _extends2.default)({
    tagName: tagName,
    placeholder: (0, _i18n.__)('Site Title'),
    value: title,
    onChange: setTitle,
    allowedFormats: [],
    disableLineBreaks: true
  }, blockWrapperProps)));
}
//# sourceMappingURL=index.js.map