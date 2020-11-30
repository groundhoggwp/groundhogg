import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { ENTER } from '@wordpress/keycodes';
import { getDefaultBlockName, createBlock } from '@wordpress/blocks';

var DEFAULT_TEXT = __('Read more');

export default function MoreEdit(_ref) {
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

    if (keyCode === ENTER) {
      insertBlocksAfter([createBlock(getDefaultBlockName())]);
    }
  };

  var getHideExcerptHelp = function getHideExcerptHelp(checked) {
    return checked ? __('The excerpt is hidden.') : __('The excerpt is visible.');
  };

  var toggleHideExcerpt = function toggleHideExcerpt() {
    return setAttributes({
      noTeaser: !noTeaser
    });
  };

  var style = {
    width: "".concat((customText ? customText : DEFAULT_TEXT).length + 1.2, "em")
  };
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, null, createElement(ToggleControl, {
    label: __('Hide the excerpt on the full content page'),
    checked: !!noTeaser,
    onChange: toggleHideExcerpt,
    help: getHideExcerptHelp
  }))), createElement("div", {
    className: "wp-block-more"
  }, createElement("input", {
    "aria-label": __('Read more link text'),
    type: "text",
    value: customText,
    placeholder: DEFAULT_TEXT,
    onChange: onChangeInput,
    onKeyDown: onKeyDown,
    style: style
  })));
}
//# sourceMappingURL=edit.js.map