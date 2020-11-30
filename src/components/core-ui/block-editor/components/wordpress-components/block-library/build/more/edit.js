"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = MoreEdit;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _keycodes = require("@wordpress/keycodes");

var _blocks = require("@wordpress/blocks");

/**
 * WordPress dependencies
 */
var DEFAULT_TEXT = (0, _i18n.__)('Read more');

function MoreEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      customText = _ref$attributes.customText,
      noTeaser = _ref$attributes.noTeaser,
      insertBlocksAfter = _ref.insertBlocksAfter,
      setAttributes = _ref.setAttributes;

  var onChangeInput = function onChangeInput(event) {
    setAttributes({
      customText: event.target.value !== '' ? event.target.value : undefined
    });
  };

  var onKeyDown = function onKeyDown(_ref2) {
    var keyCode = _ref2.keyCode;

    if (keyCode === _keycodes.ENTER) {
      insertBlocksAfter([(0, _blocks.createBlock)((0, _blocks.getDefaultBlockName)())]);
    }
  };

  var getHideExcerptHelp = function getHideExcerptHelp(checked) {
    return checked ? (0, _i18n.__)('The excerpt is hidden.') : (0, _i18n.__)('The excerpt is visible.');
  };

  var toggleHideExcerpt = function toggleHideExcerpt() {
    return setAttributes({
      noTeaser: !noTeaser
    });
  };

  var style = {
    width: "".concat((customText ? customText : DEFAULT_TEXT).length + 1.2, "em")
  };
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, null, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Hide the excerpt on the full content page'),
    checked: !!noTeaser,
    onChange: toggleHideExcerpt,
    help: getHideExcerptHelp
  }))), (0, _element.createElement)("div", {
    className: "wp-block-more"
  }, (0, _element.createElement)("input", {
    "aria-label": (0, _i18n.__)('Read more link text'),
    type: "text",
    value: customText,
    placeholder: DEFAULT_TEXT,
    onChange: onChangeInput,
    onKeyDown: onKeyDown,
    style: style
  })));
}
//# sourceMappingURL=edit.js.map