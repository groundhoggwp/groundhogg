"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _blocks = require("@wordpress/blocks");

var _keycodes = require("@wordpress/keycodes");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockPreview = _interopRequireDefault(require("../block-preview"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockPattern(_ref) {
  var pattern = _ref.pattern,
      _onClick = _ref.onClick;
  var content = pattern.content,
      viewportWidth = pattern.viewportWidth;
  var blocks = (0, _element.useMemo)(function () {
    return (0, _blocks.parse)(content);
  }, [content]);
  var instanceId = (0, _compose.useInstanceId)(BlockPattern);
  var descriptionId = "block-editor-block-patterns-list__item-description-".concat(instanceId);
  return (0, _element.createElement)("div", {
    className: "block-editor-block-patterns-list__item",
    role: "button",
    onClick: function onClick() {
      return _onClick(pattern, blocks);
    },
    onKeyDown: function onKeyDown(event) {
      if (_keycodes.ENTER === event.keyCode || _keycodes.SPACE === event.keyCode) {
        _onClick(pattern, blocks);
      }
    },
    tabIndex: 0,
    "aria-label": pattern.title,
    "aria-describedby": pattern.description ? descriptionId : undefined
  }, (0, _element.createElement)(_blockPreview.default, {
    blocks: blocks,
    viewportWidth: viewportWidth
  }), (0, _element.createElement)("div", {
    className: "block-editor-block-patterns-list__item-title"
  }, pattern.title), !!pattern.description && (0, _element.createElement)(_components.VisuallyHidden, {
    id: descriptionId
  }, pattern.description));
}

function BlockPatternPlaceholder() {
  return (0, _element.createElement)("div", {
    className: "block-editor-block-patterns-list__item is-placeholder"
  });
}

function BlockPatternList(_ref2) {
  var blockPatterns = _ref2.blockPatterns,
      shownPatterns = _ref2.shownPatterns,
      onClickPattern = _ref2.onClickPattern;
  return blockPatterns.map(function (pattern) {
    var isShown = shownPatterns.includes(pattern);
    return isShown ? (0, _element.createElement)(BlockPattern, {
      key: pattern.name,
      pattern: pattern,
      onClick: onClickPattern
    }) : (0, _element.createElement)(BlockPatternPlaceholder, {
      key: pattern.name
    });
  });
}

var _default = BlockPatternList;
exports.default = _default;
//# sourceMappingURL=index.js.map