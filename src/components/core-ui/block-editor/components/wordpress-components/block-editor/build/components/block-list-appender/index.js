"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _defaultBlockAppender = _interopRequireDefault(require("../default-block-appender"));

var _buttonBlockAppender = _interopRequireDefault(require("../button-block-appender"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function stopPropagation(event) {
  event.stopPropagation();
}

function BlockListAppender(_ref) {
  var blockClientIds = _ref.blockClientIds,
      rootClientId = _ref.rootClientId,
      canInsertDefaultBlock = _ref.canInsertDefaultBlock,
      isLocked = _ref.isLocked,
      CustomAppender = _ref.renderAppender,
      className = _ref.className,
      selectedBlockClientId = _ref.selectedBlockClientId,
      _ref$tagName = _ref.tagName,
      TagName = _ref$tagName === void 0 ? 'div' : _ref$tagName;

  if (isLocked || CustomAppender === false) {
    return null;
  }

  var appender;

  if (CustomAppender) {
    // Prefer custom render prop if provided.
    appender = (0, _element.createElement)(CustomAppender, null);
  } else {
    var isDocumentAppender = !rootClientId;
    var isParentSelected = selectedBlockClientId === rootClientId;
    var isAnotherDefaultAppenderAlreadyDisplayed = selectedBlockClientId && !blockClientIds.includes(selectedBlockClientId);

    if (!isDocumentAppender && !isParentSelected && (!selectedBlockClientId || isAnotherDefaultAppenderAlreadyDisplayed)) {
      return null;
    }

    if (canInsertDefaultBlock) {
      // Render the default block appender when renderAppender has not been
      // provided and the context supports use of the default appender.
      appender = (0, _element.createElement)(_defaultBlockAppender.default, {
        rootClientId: rootClientId,
        lastBlockClientId: (0, _lodash.last)(blockClientIds)
      });
    } else {
      // Fallback in the case no renderAppender has been provided and the
      // default block can't be inserted.
      appender = (0, _element.createElement)(_buttonBlockAppender.default, {
        rootClientId: rootClientId,
        className: "block-list-appender__toggle"
      });
    }
  }

  return (0, _element.createElement)(TagName // A `tabIndex` is used on the wrapping `div` element in order to
  // force a focus event to occur when an appender `button` element
  // is clicked. In some browsers (Firefox, Safari), button clicks do
  // not emit a focus event, which could cause this event to propagate
  // unexpectedly. The `tabIndex` ensures that the interaction is
  // captured as a focus, without also adding an extra tab stop.
  //
  // See: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
  , {
    tabIndex: -1 // Prevent the block from being selected when the appender is
    // clicked.
    ,
    onFocus: stopPropagation,
    className: (0, _classnames.default)('block-list-appender', className)
  }, appender);
}

var _default = (0, _data.withSelect)(function (select, _ref2) {
  var rootClientId = _ref2.rootClientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder,
      canInsertBlockType = _select.canInsertBlockType,
      getTemplateLock = _select.getTemplateLock,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  return {
    isLocked: !!getTemplateLock(rootClientId),
    blockClientIds: getBlockOrder(rootClientId),
    canInsertDefaultBlock: canInsertBlockType((0, _blocks.getDefaultBlockName)(), rootClientId),
    selectedBlockClientId: getSelectedBlockClientId()
  };
})(BlockListAppender);

exports.default = _default;
//# sourceMappingURL=index.js.map