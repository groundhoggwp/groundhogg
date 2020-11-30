"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _dom = require("../../utils/dom");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var SkipToSelectedBlock = function SkipToSelectedBlock(_ref) {
  var selectedBlockClientId = _ref.selectedBlockClientId;

  var onClick = function onClick() {
    var selectedBlockElement = (0, _dom.getBlockDOMNode)(selectedBlockClientId);
    selectedBlockElement.focus();
  };

  return selectedBlockClientId && (0, _element.createElement)(_components.Button, {
    isSecondary: true,
    className: "block-editor-skip-to-selected-block",
    onClick: onClick
  }, (0, _i18n.__)('Skip to the selected block'));
};

var _default = (0, _data.withSelect)(function (select) {
  return {
    selectedBlockClientId: select('core/block-editor').getBlockSelectionStart()
  };
})(SkipToSelectedBlock);

exports.default = _default;
//# sourceMappingURL=index.js.map