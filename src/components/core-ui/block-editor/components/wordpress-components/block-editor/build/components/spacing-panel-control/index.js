"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = SpacingPanelControl;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _inspectorControls = _interopRequireDefault(require("../inspector-controls"));

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function SpacingPanelControl(_ref) {
  var children = _ref.children,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children"]);
  var isSpacingEnabled = (0, _useEditorFeature.default)('spacing.customPadding');
  if (!isSpacingEnabled) return null;
  return (0, _element.createElement)(_inspectorControls.default, props, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Spacing')
  }, children));
}
//# sourceMappingURL=index.js.map