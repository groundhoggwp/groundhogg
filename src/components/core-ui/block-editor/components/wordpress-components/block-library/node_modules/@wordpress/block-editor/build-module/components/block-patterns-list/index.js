import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { parse } from '@wordpress/blocks';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { VisuallyHidden } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BlockPreview from '../block-preview';

function BlockPattern(_ref) {
  var pattern = _ref.pattern,
      _onClick = _ref.onClick;
  var content = pattern.content,
      viewportWidth = pattern.viewportWidth;
  var blocks = useMemo(function () {
    return parse(content);
  }, [content]);
  var instanceId = useInstanceId(BlockPattern);
  var descriptionId = "block-editor-block-patterns-list__item-description-".concat(instanceId);
  return createElement("div", {
    className: "block-editor-block-patterns-list__item",
    role: "button",
    onClick: function onClick() {
      return _onClick(pattern, blocks);
    },
    onKeyDown: function onKeyDown(event) {
      if (ENTER === event.keyCode || SPACE === event.keyCode) {
        _onClick(pattern, blocks);
      }
    },
    tabIndex: 0,
    "aria-label": pattern.title,
    "aria-describedby": pattern.description ? descriptionId : undefined
  }, createElement(BlockPreview, {
    blocks: blocks,
    viewportWidth: viewportWidth
  }), createElement("div", {
    className: "block-editor-block-patterns-list__item-title"
  }, pattern.title), !!pattern.description && createElement(VisuallyHidden, {
    id: descriptionId
  }, pattern.description));
}

function BlockPatternPlaceholder() {
  return createElement("div", {
    className: "block-editor-block-patterns-list__item is-placeholder"
  });
}

function BlockPatternList(_ref2) {
  var blockPatterns = _ref2.blockPatterns,
      shownPatterns = _ref2.shownPatterns,
      onClickPattern = _ref2.onClickPattern;
  return blockPatterns.map(function (pattern) {
    var isShown = shownPatterns.includes(pattern);
    return isShown ? createElement(BlockPattern, {
      key: pattern.name,
      pattern: pattern,
      onClick: onClickPattern
    }) : createElement(BlockPatternPlaceholder, {
      key: pattern.name
    });
  });
}

export default BlockPatternList;
//# sourceMappingURL=index.js.map