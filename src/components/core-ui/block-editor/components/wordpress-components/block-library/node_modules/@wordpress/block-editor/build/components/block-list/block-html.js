"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactAutosizeTextarea = _interopRequireDefault(require("react-autosize-textarea"));

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function BlockHTML(_ref) {
  var clientId = _ref.clientId;

  var _useState = (0, _element.useState)(''),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      html = _useState2[0],
      setHtml = _useState2[1];

  var block = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]);

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlock = _useDispatch.updateBlock;

  var onChange = function onChange() {
    var blockType = (0, _blocks.getBlockType)(block.name);
    var attributes = (0, _blocks.getBlockAttributes)(blockType, html, block.attributes); // If html is empty  we reset the block to the default HTML and mark it as valid to avoid triggering an error

    var content = html ? html : (0, _blocks.getSaveContent)(blockType, attributes);
    var isValid = html ? (0, _blocks.isValidBlockContent)(blockType, attributes, content) : true;
    updateBlock(clientId, {
      attributes: attributes,
      originalContent: content,
      isValid: isValid
    }); // Ensure the state is updated if we reset so it displays the default content

    if (!html) {
      setHtml({
        content: content
      });
    }
  };

  (0, _element.useEffect)(function () {
    setHtml((0, _blocks.getBlockContent)(block));
  }, [block]);
  return (0, _element.createElement)(_reactAutosizeTextarea.default, {
    className: "block-editor-block-list__block-html-textarea",
    value: html,
    onBlur: onChange,
    onChange: function onChange(event) {
      return setHtml(event.target.value);
    }
  });
}

var _default = BlockHTML;
exports.default = _default;
//# sourceMappingURL=block-html.js.map